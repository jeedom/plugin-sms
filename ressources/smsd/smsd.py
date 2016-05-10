# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import requests
import pdb
import string
import sys
import os
import time
import datetime
import binascii
import traceback
import subprocess
import threading
from threading import Thread, Event, Timer
import re
import signal
import xml.dom.minidom as minidom
from optparse import OptionParser
import socket
import select
import inspect
from os.path import join
import serial
import json
from gsmmodem.modem import GsmModem

try:
	from jeedom.jeedom import *
except ImportError:
	print "Error: importing module from jeedom folder"
	sys.exit(1)

################################PARAMETERS######################################

gsm = False

def handleSms(sms):
	logging.debug("Got SMS message : "+str(sms))
	if not sms.text:
		logging.debug("No text so nothing to do")
		return
	message = jeedom_utils.remove_accents(sms.text.replace('"', ''))
	jeedom_com.add_changes('devices::'+str(sms.number),{'number' : sms.number, 'message' : message });

def listen():
	global gsm
	jeedom_socket.open()
	logging.debug("Start listening...")
	try:
		logging.debug("Connecting to GSM Modem...")
		gsm = GsmModem(_device, int(_serial_rate), smsReceivedCallbackFunc=handleSms)
		if _text_mode == 'yes' : 
			logging.debug("Text mode true")
			gsm.smsTextMode = True 
		else :
			logging.debug("Text mode false")
			gsm.smsTextMode = False 
		if _pin != 'None':
			logging.debug("Enter pin code : "+_pin)
			gsm.connect(_pin)
		else :
			gsm.connect()
		if _smsc != 'None' :
			logging.debug("Configure smsc : "+_smsc)
			gsm.write('AT+CSCA="{0}"'.format(_smsc))
		logging.debug("Waiting for network...")
		gsm.waitForNetworkCoverage()
		logging.debug("Ok")
		try:
			jeedom_com.send_change_immediate({'number' : 'network_name', 'message' : str(gsm.networkName) });
		except Exception, e:
			logging.error("Exception: %s" % str(e))

		try:
			gsm.write('AT+CPMS="ME","ME","ME"')
			gsm.write('AT+CMGD=1,4')
		except Exception, e:
			logging.error("Exception: %s" % str(e))

		try:
			gsm.write('AT+CPMS="SM","SM","SM"')
			gsm.write('AT+CMGD=1,4')
		except Exception, e:
			logging.error("Exception: %s" % str(e))
	except Exception, e:
		logging.error("Exception: %s" % str(e))
		jeedom_com.send_change_immediate({'number' : 'none', 'message' : str(e) });
		exit(1)
	signal_strength_store = 0				
	try:
		while 1:
			time.sleep(1)
			try:
				gsm.waitForNetworkCoverage()
				gsm.processStoredSms(True)
				if signal_strength_store != gsm.signalStrength:
					signal_strength_store = gsm.signalStrength
					jeedom_com.send_change_immediate({'number' : 'signal_strength', 'message' : str(gsm.signalStrength)});
			except Exception, e:
				logging.error("Exception: %s" % str(e))
			try:
				read_socket()
			except Exception, e:
				logging.error("Exception: %s" % str(e))
	except KeyboardInterrupt:
		shutdown()

def read_socket():
	try:
		global JEEDOM_SOCKET_MESSAGE
		if not JEEDOM_SOCKET_MESSAGE.empty():
			logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
			message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
			if message['apikey'] != _apikey:
				logging.error("Invalid apikey from socket : " + str(message))
				return
			gsm.waitForNetworkCoverage()
			gsm.sendSms(message['number'],message['message'])
	except Exception,e:
		logging.error(str(e))

def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting..." % int(signum))
	shutdown()
	
def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file " + str(_pidfile))
	try:
		os.remove(_pidfile)
	except:
		pass
	try:
		jeedom_socket.close()
	except:
		pass
	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

# ----------------------------------------------------------------------------

_log_level = "error"
_socket_port = 55002
_socket_host = 'localhost'
_device = 'auto'
_pidfile = '/tmp/smsd.pid'
_apikey = ''
_callback = ''
_cycle = 0.5;
_serial_rate = 9600
_pin = 'None'
_text_mode = 'no'
_smsc = 'None'

for arg in sys.argv:
	if arg.startswith("--loglevel="):
		temp, _log_level = arg.split("=")
		jeedom_utils.set_log_level(_log_level)
	elif arg.startswith("--socketport="):
		temp, _socket_port = arg.split("=")
	elif arg.startswith("--sockethost="):
		temp, _socket_host = arg.split("=")
	elif arg.startswith("--pidfile="):
		temp, _pidfile = arg.split("=")
	elif arg.startswith("--device="):
		temp, _device = arg.split("=")
	elif arg.startswith("--apikey="):
		temp, _apikey = arg.split("=")
	elif arg.startswith("--callback="):
		temp, _callback = arg.split("=")
	elif arg.startswith("--cycle="):
		temp, _cycle = arg.split("=")
	elif arg.startswith("--serialrate="):
		temp, _serial_rate = arg.split("=")
	elif arg.startswith("--pin="):
		temp, _pin = arg.split("=")
	elif arg.startswith("--textmode="):
		temp, _text_mode = arg.split("=")
	elif arg.startswith("--smsc="):
		temp, _smsc = arg.split("=")

_socket_port = int(_socket_port)
_cycle = float(_cycle)

logging.info('Start SMSCMD')
logging.info('Log level : '+str(_log_level))
logging.info('Socket port : '+str(_socket_port))
logging.info('Socket host : '+str(_socket_host))
logging.info('PID file : '+str(_pidfile))
logging.info('Device : '+str(_device))
logging.info('Apikey : '+str(_apikey))
logging.info('Callback : '+str(_callback))
logging.info('Cycle : '+str(_cycle))
logging.info('Serial rate : '+str(_serial_rate))
logging.info('Pin : '+str(_pin))
logging.info('Text mode : '+str(_text_mode))
logging.info('SMSC : '+str(_smsc))

if _device == 'auto':
	_device = jeedom_utils.find_tty_usb('12d1','1003')
	logging.info('Find device : '+str(_device))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)	

try:
	jeedom_utils.wrtie_pid(str(_pidfile))
	jeedom_com = jeedom_com(apikey = _apikey,url = _callback,cycle=_cycle)
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception,e:
	logging.error('Fatal error : '+str(e))
	shutdown()