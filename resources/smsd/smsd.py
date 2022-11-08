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
import sys
import os
import time
import argparse
import signal
import traceback
import json
from gsmmodem.modem import GsmModem

try:
    from jeedom.jeedom import *
except ImportError as e:
    print("Error: importing module from jeedom folder: %s", e)
    sys.exit(1)

################################PARAMETERS######################################

gsm = False


def handleSms(sms):
    logging.debug("Got SMS message : "+str(sms))
    if not sms.text:
        logging.debug("No text so nothing to do")
        return
    message = jeedom_utils.remove_accents(sms.text.replace('"', ''))
    jeedom_com.add_changes('devices::'+str(sms.number), {'number': sms.number, 'message': message})


def listen():
    global gsm
    jeedom_socket.open()
    logging.debug("Start listening...")
    try:
        logging.debug("Connecting to GSM Modem...")
        gsm = GsmModem(_device, int(_serial_rate), smsReceivedCallbackFunc=handleSms)
        if _text_mode == 'yes':
            logging.debug("Text mode true")
            gsm.smsTextMode = True
        else:
            logging.debug("Text mode false")
            gsm.smsTextMode = False
        if _pin != 'None':
            logging.debug("Enter pin code : "+_pin)
            gsm.connect(_pin, 1)
        else:
            gsm.connect(None, 1)
        if _smsc != 'None':
            logging.debug("Configure smsc : "+_smsc)
            gsm.write('AT+CSCA="{0}"'.format(_smsc))
        logging.debug("Waiting for network...")
        gsm.waitForNetworkCoverage()
        logging.debug("Ok")
        try:
            jeedom_com.send_change_immediate({'number': 'network_name', 'message': str(gsm.networkName)})
        except Exception as e:
            if str(e).find('object has no attribute') != -1:
                pass
            logging.error("Exception during send_change_immediate: %s" % str(e))

        try:
            gsm.write('AT+CPMS="ME","ME","ME"')
            gsm.write('AT+CMGD=1,4')
        except Exception as e:
            if str(e).find('object has no attribute') != -1:
                pass
            logging.error("Exception on 'ME': %s" % str(e))

        try:
            gsm.write('AT+CPMS="SM","SM","SM"')
            gsm.write('AT+CMGD=1,4')
        except Exception as e:
            if str(e).find('object has no attribute') != -1:
                pass
            logging.error("Exception on 'SM': %s" % str(e))
    except Exception as e:
        if str(e).find('object has no attribute') != -1:
            pass
        if str(e).find('Attempting to use a port that is not open') != -1:
            pass
        logging.error("Global listen exception of type %s occurred: %s", type(e).__name__, e)
        jeedom_com.send_change_immediate({'number': 'none', 'message': str(e)})
        logging.error("Exit 1 because this exeption is fatal")
        shutdown()
    signal_strength_store = 0
    try:
        while 1:
            time.sleep(_cycle)
            try:
                gsm.waitForNetworkCoverage()
                gsm.processStoredSms(True)
                if signal_strength_store != gsm.signalStrength:
                    signal_strength_store = gsm.signalStrength
                    jeedom_com.send_change_immediate({'number': 'signal_strength', 'message': str(gsm.signalStrength)})
            except Exception as e:
                logging.error("Exception on GSM : %s" % str(e))
                if str(e) == 'Attempting to use a port that is not open' or str(e) == 'Timeout' or str(e) == 'Device not searching for network operator':
                    logging.error("Exit 1 because this exeption is fatal")
                    shutdown()
            try:
                read_socket()
            except Exception as e:
                logging.error("Exception on socket : %s" % str(e))
    except KeyboardInterrupt:
        shutdown()


def read_socket():
    try:
        global JEEDOM_SOCKET_MESSAGE
        if not JEEDOM_SOCKET_MESSAGE.empty():
            logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
            message = json.loads(JEEDOM_SOCKET_MESSAGE.get().decode("utf-8"))
            if message['apikey'] != _apikey:
                logging.error("Invalid apikey from socket : " + str(message))
                return
            gsm.waitForNetworkCoverage()
            logging.info("Envoi d'un message à %s: %s", message['number'], message['message'])
            gsm.sendSms(message['number'], message['message'])
    except Exception as e:
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
_socket_host = '127.0.0.1'
_device = 'auto'
_pidfile = '/tmp/smsd.pid'
_apikey = ''
_callback = ''
_cycle = 30
_serial_rate = 9600
_pin = 'None'
_text_mode = 'no'
_smsc = 'None'


parser = argparse.ArgumentParser(description='SMS Daemon for Jeedom plugin')
parser.add_argument("--device", help="Device", type=str)
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--serialrate", help="Serial rate of device", type=str)
parser.add_argument("--pin", help="Pin sim code", type=str)
parser.add_argument("--textmode", help="Force text mode", type=str)
parser.add_argument("--smsc", help="Smsc number", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
args = parser.parse_args()

if args.device:
    _device = args.device
if args.socketport:
    _socket_port = int(args.socketport)
if args.loglevel:
    _log_level = args.loglevel
if args.callback:
    _callback = args.callback
if args.apikey:
    _apikey = args.apikey
if args.cycle:
    _cycle = float(args.cycle)
if args.serialrate:
    _serial_rate = int(args.serialrate)
if args.pin:
    _pin = args.pin
if args.textmode:
    _text_mode = args.textmode
if args.smsc:
    _smsc = args.smsc
if args.pid:
    _pidfile = args.pid

_socket_port = int(_socket_port)
_cycle = float(_cycle)

jeedom_utils.set_log_level(_log_level)

logging.info('Start smsd')
logging.info('Log level : '+str(_log_level))
logging.info('Socket port : '+str(_socket_port))
logging.info('Socket host : '+str(_socket_host))
logging.info('PID file : '+str(_pidfile))
logging.info('Device : '+str(_device))
logging.info('Callback : '+str(_callback))
logging.info('Cycle : '+str(_cycle))
logging.info('Serial rate : '+str(_serial_rate))
logging.info('Pin : '+str(_pin))
logging.info('Text mode : '+str(_text_mode))
logging.info('SMSC : '+str(_smsc))


if _device == 'auto':
    know_sticks = [{'idVendor': '12d1', 'idProduct': '1003', 'name': 'Huawei'},
                   {'idVendor': '12d1', 'idProduct': '1f01', 'name': 'Huawei'},
                   {'idVendor': '12d1', 'idProduct': '1001', 'name': 'Huawei'},
                   {'idVendor': '0403', 'idProduct': '6001', 'name': 'Gsm'}]
    for stick in know_sticks:
        _device = jeedom_utils.find_tty_usb(stick['idVendor'], stick['idProduct'], stick['name'])
        if _device is not None:
            logging.info('Find device : '+str(_device))
            break


if _device is None:
    logging.error('No device found')
    shutdown()

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedom_com = jeedom_com(apikey=_apikey, url=_callback, cycle=_cycle)
    if not jeedom_com.test():
        logging.error('Network communication issues. Please fixe your Jeedom network configuration.')
        shutdown()
    jeedom_socket = jeedom_socket(port=_socket_port, address=_socket_host)
    listen()
except Exception as e:
    logging.error('Fatal error : '+str(e))
    logging.debug(traceback.format_exc())
    shutdown()
