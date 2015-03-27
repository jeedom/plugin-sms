#!/usr/bin/env python
# vim: ai ts=4 sts=4 et sw=4
# see LICENSE file (it's BSD)

__author__ = "Gevrey Loic"
__copyright__ = "Copyright 2014-2015, Gevrey Loic"
__license__ = "GPL"
__version__ = "0.1"
__maintainer__ = "Gevrey Loic"
__email__ = "loic@jeedom.com"
__status__ = "Development-Alpha-1"
__date__ = "$Date: 2014-10-02$"


import time
import logging
import threading
import subprocess
import signal
import json
import sys
import os
import socket
import inspect
import xml.dom.minidom as minidom
from optparse import OptionParser
from gsmmodem.modem import GsmModem
import unicodedata
from os.path import join

from Queue import Queue
messageQueue = Queue()

import SocketServer
SocketServer.TCPServer.allow_reuse_address = True
from SocketServer import (TCPServer, StreamRequestHandler)

################################PARAMETERS######################################

class config_data:
	def __init__(
		self,
		serial_device = None,
		trigger_active = False,
		trigger_file = "",
		trigger_timeout = 10,
		loglevel = "info",
		logfile = "gsmcmd.log",
		program_path = "",
		socketserver = False,
		sockethost = "",
		socketport = "",
		pin = "None",
		text_mode = "no",
		smsc = "None",
		daemon_active = False,
		daemon_pidfile = "gsm.pid",
				debug = False
		):

		self.serial_device = serial_device
		self.trigger_file = trigger_file
		self.trigger_timeout = trigger_timeout
		self.loglevel = loglevel
		self.logfile = logfile
		self.text_mode = text_mode
		self.pin = pin
		self.smsc = smsc
		self.program_path = program_path
		self.socketserver = socketserver
		self.sockethost = sockethost
		self.socketport = socketport
		self.debug = debug
		self.daemon_active = daemon_active
		self.daemon_pidfile = daemon_pidfile

class cmdarg_data:
	def __init__(
		self,
		configfile = "",
		action = "",
		rawcmd = "",
		device = "",
		createpid = False,
		pidfile = "",
		printout_complete = True,
		printout_csv = False
		):

		self.configfile = configfile
		self.action = action
		self.rawcmd = rawcmd
		self.device = device
		self.createpid = createpid
		self.pidfile = pidfile

gsm = False

##############################COMMAND###########################################

class Command(object):
	def __init__(self, cmd):
		self.cmd = cmd
		self.process = None
	
	def run(self, timeout):
		def target():
			logger.debug("Thread started, timeout = " + str(timeout))
			self.process = subprocess.Popen(self.cmd, shell=True)
			self.process.communicate()
			logger.debug("Return code: " + str(self.process.returncode))
			logger.debug("Thread finished")
			self.timer.cancel()
		
		def timer_callback():
			logger.debug("Thread timeout, terminate it")
			if self.process.poll() is None:
				try:
					self.process.terminate()
				except OSError as error:
					logger.error("Error: %s " % error)
				logger.debug("Thread terminated")
			else:
				logger.debug("Thread not alive")
			
		thread = threading.Thread(target=target)
		self.timer = threading.Timer(int(timeout), timer_callback)
		self.timer.start()
		thread.start()

##############################READ SOCKET#######################################
# ----------------------------------------------------------------------------

class NetRequestHandler(StreamRequestHandler):
	
	def handle(self):
		logger.debug("Client connected to [%s:%d]" % self.client_address)
		lg = self.rfile.readline()
		messageQueue.put(lg)
		logger.debug("Message read from socket: " + lg.strip())
		self.netAdapterClientConnected = False
		logger.debug("Client disconnected from [%s:%d]" % self.client_address)
	
class GSMcmdSocketAdapter(object, StreamRequestHandler):
	def __init__(self, address='localhost', port=55002):
		self.Address = address
		self.Port = port

		self.netAdapter = TCPServer((self.Address, self.Port), NetRequestHandler)
		if self.netAdapter:
			self.netAdapterRegistered = True
			threading.Thread(target=self.loopNetServer, args=()).start()

	def loopNetServer(self):
		logger.debug("LoopNetServer Thread started")
		logger.debug("Listening on: [%s:%d]" % (self.Address, self.Port))
		self.netAdapter.serve_forever()
		logger.debug("LoopNetServer Thread stopped")

# ----------------------------------------------------------------------------
# C __LINE__ equivalent in Python by Elf Sternberg
# http://www.elfsternberg.com/2008/09/23/c-__line__-equivalent-in-python/
# ----------------------------------------------------------------------------

def _line():
	info = inspect.getframeinfo(inspect.currentframe().f_back)[0:3]
	return '[%s:%d]' % (info[2], info[1])

# ----------------------------------------------------------------------------

def stripped(str):
	"""
	Strip all characters that are not valid
	Credit: http://rosettacode.org/wiki/Strip_control_codes_and_extended_characters_from_a_string
	"""
	return "".join([i for i in str if ord(i) in range(32, 127)])

# ----------------------------------------------------------------------------

def remove_accents(input_str):
	nkfd_form = unicodedata.normalize('NFKD', unicode(input_str))
	return u"".join([c for c in nkfd_form if not unicodedata.combining(c)])

def handleSms(sms):
	logger.debug("Got SMS message : "+str(sms))
	message = remove_accents(sms.text.replace('"', ''))
	action = config.trigger.replace('&quot;', '"').replace('&amp;', '&').replace("$number$",sms.number).replace("$message$", message )
	logger.debug("Execute shell : "+action)
	command = Command(action)
	command.run(timeout=config.trigger_timeout)

def option_listen():
	"""
	Listen to GSM device and process data, exit with CTRL+C
	"""
	global gsm
	logger.debug("Start listening...")
		
	try:
		logger.debug("Connecting to GSM Modem...")
		if config.debug :
			logging.basicConfig(format='%(levelname)s: %(message)s', level=logging.DEBUG)

		gsm = GsmModem(config.serial_device, 115200, smsReceivedCallbackFunc=handleSms)
		if config.text_mode == 'yes' : 
			gsm.smsTextMode = True 
		else :
			gsm.smsTextMode = False 

		if config.pin != 'None' :
			gsm.connect(config.pin)
		else :
			gsm.connect()
		if config.smsc != 'None' :
			logger.debug("Configure smsc : "+config.smsc)
			gsm.write('AT+CSCA="{0}"'.format(config.smsc))
			

		logger.debug("Waiting for network...")
		gsm.waitForNetworkCoverage()
		logger.debug("Ok")

		try:
			action = config.trigger.replace('&quot;', '"').replace('&amp;', '&').replace("$number$",'network_name').replace("$message$", str(gsm.networkName) )
			logger.debug("Execute shell : "+action)
			command = Command(action)
			command.run(timeout=config.trigger_timeout)
		except Exception, e:
			print("Exception: %s" % str(e))

		try:
			gsm.write('AT+CPMS="ME","ME","ME"')
			gsm.write('AT+CMGD=1,4')
		except Exception, e:
			print("Exception: %s" % str(e))

		try:
			gsm.write('AT+CPMS="SM","SM","SM"')
			gsm.write('AT+CMGD=1,4')
		except Exception, e:
			print("Exception: %s" % str(e))

	except Exception, e:
		print("Exception: %s" % str(e))
		logger.error("Exception: %s" % str(e))
		action = config.trigger.replace('&quot;', '"').replace('&amp;', '&').replace("$number$",'none').replace("$message$", str(e) )
		logger.debug("Execute shell : "+action)
		command = Command(action)
		command.run(timeout=config.trigger_timeout)
		exit(1)

	if config.socketserver:
		try:
			serversocket = GSMcmdSocketAdapter(config.sockethost,int(config.socketport))
		except Exception, e:
			logger.error("Error starting socket server. Line: " + _line())
			logger.error("Error: %s" % str(e))
			print("Error: can not start server socket, another instance already running?")
			exit(1)
		if serversocket.netAdapterRegistered:
			logger.debug("Socket interface started")
		else:
			logger.debug("Cannot start socket interface")

		signal_strength_store = 0				
	try:
		while 1:
			# Let it breath
			# Without this sleep it will cause 100% CPU in windows
			time.sleep(1)
			try:
				gsm.waitForNetworkCoverage()
				gsm.processStoredSms(True)

				if signal_strength_store != gsm.signalStrength:
					signal_strength_store = gsm.signalStrength
					action = config.trigger.replace('&quot;', '"').replace('&amp;', '&').replace("$number$",'signal_strength').replace("$message$", str(gsm.signalStrength) )
					logger.debug("Execute shell : "+action)
					command = Command(action)
					command.run(timeout=config.trigger_timeout)

			except Exception, e:
				print("Exception: %s" % str(e))
				#logger.debug("Exception: %s" % str(e))

			try:
				if config.socketserver:
					read_socket()
			except Exception, e:
				print("Exception: %s" % str(e))
				logger.error("Exception: %s" % str(e))
			
	except KeyboardInterrupt:
		logger.debug("Received keyboard interrupt")
		logger.debug("Close server socket")
		serversocket.netAdapter.shutdown()
		
		if config.serial_active:
			logger.debug("Close serial port")
			close_serialport()
		
		print("\nExit...")
		pass


# ----------------------------------------------------------------------------
# DEAMONIZE
# Credit: George Henze
# ----------------------------------------------------------------------------

def shutdown():
	# clean up PID file after us
	logger.debug("Shutdown")

	if cmdarg.createpid:
		logger.debug("Removing PID file " + str(cmdarg.pidfile))
		os.remove(cmdarg.pidfile)

	logger.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)
	
def handler(signum=None, frame=None):
	if type(signum) != type(None):
		logger.debug("Signal %i caught, exiting..." % int(signum))
		shutdown()

def daemonize():

	try:
		pid = os.fork()
		if pid != 0:
			sys.exit(0)
	except OSError, e:
		raise RuntimeError("1st fork failed: %s [%d]" % (e.strerror, e.errno))

	os.setsid() 

	prev = os.umask(0)
	os.umask(prev and int('077', 8))

	try:
		pid = os.fork() 
		if pid != 0:
			sys.exit(0)
	except OSError, e:
		raise RuntimeError("2nd fork failed: %s [%d]" % (e.strerror, e.errno))

	dev_null = file('/dev/null', 'r')
	os.dup2(dev_null.fileno(), sys.stdin.fileno())

	if cmdarg.createpid == True:
		pid = str(os.getpid())
		logger.debug("Writing PID " + pid + " to " + str(cmdarg.pidfile))
		file(cmdarg.pidfile, 'w').write("%s\n" % pid)

def read_socket():
	"""
	Check socket for messages
	
	Credit: Olivier Djian
	"""
	global messageQueue

	if not messageQueue.empty():
		logger.debug("Message received in socket messageQueue")
		message = stripped(messageQueue.get())
		logger.debug("Message  : "+str(message))
		try :
			message = json.loads(message)
			gsm.waitForNetworkCoverage()
			gsm.sendSms(message['number'],message['message'])
		except Exception, e:
				print("Exception: %s" % str(e))
						

def read_configfile():
	"""
	Read items from the configuration file
	"""
	if os.path.exists( cmdarg.configfile ):

		# ----------------------
		# Serial device
		if (read_config(cmdarg.configfile, "serial_active") == "yes"):
			config.serial_active = True
		else:
			config.serial_active = False
		config.serial_device = read_config( cmdarg.configfile, "serial_device")
		if config.serial_device == 'auto':
			config.serial_device = find_tty_usb('12d1','1003')
		logger.debug("Serial device: " + str(config.serial_device))

		config.pin = read_config( cmdarg.configfile, "pin")
		logger.debug("Code pin: " + str(config.pin))
		config.smsc = read_config( cmdarg.configfile, "smsc")
		logger.debug("Smsc: " + str(config.smsc))

		config.text_mode = read_config( cmdarg.configfile, "text_mode")
		logger.debug("Code text_mode: " + str(config.text_mode))

			
		# ----------------------
		# SOCKET SERVER
		if (read_config(cmdarg.configfile, "socketserver") == "yes"):
			config.socketserver = True
		else:
			config.socketserver = False
		config.sockethost = read_config( cmdarg.configfile, "sockethost")
		config.socketport = read_config( cmdarg.configfile, "socketport")
		logger.debug("SocketServer: " + str(config.socketserver))
		logger.debug("SocketHost: " + str(config.sockethost))
		logger.debug("SocketPort: " + str(config.socketport))

		# -----------------------
		# DAEMON
		if (read_config(cmdarg.configfile, "daemon_active") == "yes"):
			config.daemon_active = True
		else:
			config.daemon_active = False
		config.daemon_pidfile = read_config( cmdarg.configfile, "daemon_pidfile")
		logger.debug("Daemon_active: " + str(config.daemon_active))
		logger.debug("Daemon_pidfile: " + str(config.daemon_pidfile))

				# TRIGGER
		config.trigger = read_config( cmdarg.configfile, "trigger").replace('&quot;', '"').replace('&amp;', '&')
		config.trigger_timeout = read_config( cmdarg.configfile, "trigger_timeout")
		
		# ------------------------
		# LOG MESSAGES
		if (read_config(cmdarg.configfile, "log_msg") == "yes"):
			config.log_msg = True
		else:
			config.log_msg = False
		config.log_msgfile = read_config(cmdarg.configfile, "log_msgfile")
		
	else:
		# config file not found, set default values
		print "Error: Configuration file not found (" + cmdarg.configfile + ")"
		logger.error("Error: Configuration file not found (" + cmdarg.configfile + ") Line: " + _line())

# ----------------------------------------------------------------------------
def read_config( configFile, configItem):
	"""
	Read item from the configuration file
	"""
	logger.debug('Open configuration file')
	logger.debug('File: ' + configFile)
	
	if os.path.exists( configFile ):

		#open the xml file for reading:
		f = open( configFile,'r')
		data = f.read()
		f.close()
	
		# xml parse file data
		logger.debug('Parse config XML data')
		try:
			dom = minidom.parseString(data)
		except:
			print "Error: problem in the config.xml file, cannot process it"
			logger.debug('Error in config.xml file')
			
		# Get config item
		logger.debug('Get the configuration item: ' + configItem)
		
		try:
			xmlTag = dom.getElementsByTagName( configItem )[0].toxml()
			logger.debug('Found: ' + xmlTag)
			xmlData = xmlTag.replace('<' + configItem + '>','').replace('</' + configItem + '>','')
			logger.debug('--> ' + xmlData)
		except:
			logger.debug('The item tag not found in the config file')
			xmlData = ""
			
		logger.debug('Return')
		
	else:
		logger.error("Error: Config file does not exists. Line: " + _line())
		
	return xmlData

# ----------------------------------------------------------------------------

def find_tty_usb(idVendor, idProduct):
    """find_tty_usb('067b', '2302') -> '/dev/ttyUSB0'"""
    # Note: if searching for a lot of pairs, it would be much faster to search
    # for the enitre lot at once instead of going over all the usb devices
    # each time.
    for dnbase in os.listdir('/sys/bus/usb/devices'):
        dn = join('/sys/bus/usb/devices', dnbase)
        if not os.path.exists(join(dn, 'idVendor')):
            continue
        idv = open(join(dn, 'idVendor')).read().strip()
        if idv != idVendor:
            continue
        idp = open(join(dn, 'idProduct')).read().strip()
        if idp != idProduct:
            continue
        for subdir in os.listdir(dn):
            if subdir.startswith(dnbase+':'):
                for subsubdir in os.listdir(join(dn, subdir)):
                    if subsubdir.startswith('ttyUSB'):
                        return join('/dev', subsubdir)

# ----------------------------------------------------------------------------

def logger_init(configfile, name, debug):
	program_path = os.path.dirname(os.path.realpath(__file__))
	dom = None
	
	if os.path.exists( os.path.join(program_path, "config.xml") ):
		# Read config file
		f = open(os.path.join(program_path, "config.xml"),'r')
		data = f.read()
		f.close()
		try:
			dom = minidom.parseString(data)
		except Exception, e:
			print "Error: problem in the config.xml file, cannot process it"
			print "Exception: %s" % str(e)
		if dom:
		
			# Get loglevel from config file
			try:
				xmlTag = dom.getElementsByTagName( 'loglevel' )[0].toxml()
				loglevel = xmlTag.replace('<loglevel>','').replace('</loglevel>','')
			except:
				loglevel = "INFO"

			# Get logfile from config file
			try:
				xmlTag = dom.getElementsByTagName( 'logfile' )[0].toxml()
				logfile = xmlTag.replace('<logfile>','').replace('</logfile>','')
			except:
				logfile = os.path.join(program_path, "enoceancmd.log")
			
			loglevel = loglevel.upper()
			
			#formatter = logging.Formatter(fmt='%(asctime)s - %(levelname)s - %(module)s - %(message)s')
			formatter = logging.Formatter('%(asctime)s - %(threadName)s - %(module)s:%(lineno)d - %(levelname)s - %(message)s')
			
			if debug:
				loglevel = "DEBUG"
				handler = logging.StreamHandler()
			else:
				handler = logging.FileHandler(logfile)
							
			handler.setFormatter(formatter)
			
			logger = logging.getLogger(name)
			logger.setLevel(logging.getLevelName(loglevel))
			logger.addHandler(handler)
			
			return logger


# ----------------------------------------------------------------------------
def main():

	global logger

	# Get directory of the enoceancmd script
	config.program_path = os.path.dirname(os.path.realpath(__file__))

	parser = OptionParser()
	parser.add_option("-d", "--device", action="store", type="string", dest="device", help="The serial device of the SMSCMD, example /dev/ttyUSB0")
	parser.add_option("-l", "--listen", action="store_true", dest="listen", help="Listen for messages from GSM device")
	parser.add_option("-o", "--config", action="store", type="string", dest="config", help="Specify the configuration file")
	parser.add_option("-v", "--verbose", action="store_true", dest="verbose", default=False, help="Output all messages to stdout")
	parser.add_option("-V", "--version", action="store_true", dest="version", help="Print enoceancmd version information")
	parser.add_option("-D", "--debug", action="store_true", dest="debug", default=False, help="Debug printout on stdout")
	(options, args) = parser.parse_args()

	# ----------------------------------------------------------
	# CONFIG FILE
	if options.config:
		cmdarg.configfile = options.config
	else:
		cmdarg.configfile = os.path.join(config.program_path, "config.xml")
				
	# ----------------------------------------------------------
	# LOGHANDLER
	if options.debug:
		config.debug = True
		logger = logger_init(cmdarg.configfile,'smscmd', True)
	else:
		logger = logger_init(cmdarg.configfile,'smscmd', False)
	
	logger.debug("Python version: %s.%s.%s" % sys.version_info[:3])
	logger.debug(__date__.replace('$', ''))

	# ----------------------------------------------------------
	# PROCESS CONFIG.XML
	logger.debug("Configfile: " + cmdarg.configfile)
	logger.debug("Read configuration file")
	read_configfile()

		# ----------------------------------------------------------
	# SERIAL
	if options.device:
		config.device = options.device
	elif config.serial_device:
		config.device = config.serial_device
	else:
		config.device = None

	# ----------------------------------------------------------
	
	logger.debug("Trigger : " + config.trigger)

	# ----------------------------------------------------------
	# DAEMON
	if config.daemon_active and options.listen:
		logger.debug("Daemon")
		logger.debug("Check PID file")
		
		if config.daemon_pidfile:
			cmdarg.pidfile = config.daemon_pidfile
			cmdarg.createpid = True
			logger.debug("PID file '" + cmdarg.pidfile + "'")
		
			if os.path.exists(cmdarg.pidfile):
				print("PID file '" + cmdarg.pidfile + "' already exists. Exiting.")
				logger.debug("PID file '" + cmdarg.pidfile + "' already exists.")
				logger.debug("Exit 1")
				sys.exit(1)
			else:
				logger.debug("PID file does not exists")

		else:
			print("You need to set the --pidfile parameter at the startup")
			logger.error("Command argument --pidfile missing. Line: " + _line())
			logger.debug("Exit 1")
			sys.exit(1)

		logger.debug("Check platform")
		logger.debug("Platform: " + sys.platform)
		try:
			logger.debug("Write PID file")
			file(cmdarg.pidfile, 'w').write("pid\n")
		except IOError, e:
			logger.error("Line: " + _line())
			logger.error("Unable to write PID file: %s [%d]" % (e.strerror, e.errno))
			raise SystemExit("Unable to write PID file: %s [%d]" % (e.strerror, e.errno))

		logger.debug("Deactivate screen printouts")
		cmdarg.printout_complete = False

		logger.debug("Start daemon")
		daemonize()

		# ----------------------------------------------------------
	# LISTEN
	if options.listen:
		option_listen()


	logger.debug("Exit 0")
	sys.exit(0)
	
# ----------------------------------------------------------------------------

def check_pythonversion():
	"""
	Check python version
	"""
	if sys.hexversion < 0x02060000:
		print "Error: Your Python need to be 2.6 or newer, please upgrade."
		sys.exit(1)

# ------------------------------------------------------------------------------

if __name__ == '__main__':

	# Init shutdown handler
	signal.signal(signal.SIGINT, handler)
	signal.signal(signal.SIGTERM, handler)

	# Init objects
	config = config_data()
	cmdarg = cmdarg_data()

	# Check python version
	check_pythonversion()
	
	main()

# ------------------------------------------------------------------------------
# END
# ------------------------------------------------------------------------------

