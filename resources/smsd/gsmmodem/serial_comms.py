""" Low-level serial communications handling """

import threading
import logging
from typing import Optional, List, Union, Callable, Literal, overload

import re
import serial  # pyserial: http://pyserial.sourceforge.net

from .exceptions import TimeoutException
# from . import compat  # For Python 2.6 compatibility


class SerialComms:
    """ Wraps all low-level serial communications (actual read/write operations) """

    log = logging.getLogger('gsmmodem.serial_comms.SerialComms')

    # End-of-line read terminator
    RX_EOL_SEQ = b'\r\n'
    # End-of-response terminator
    RESPONSE_TERM = re.compile(r'^OK|ERROR|(\+CM[ES] ERROR: \d+)|(COMMAND NOT SUPPORT)$')
    # Default timeout for serial port reads (in seconds)
    timeout = 1

    def __init__(self, port: str, baudrate: int = 115200, notifyCallbackFunc: Optional[Callable] = None, fatalErrorCallbackFunc: Optional[Callable] = None, *args, **kwargs):
        """ Constructor

        :param fatalErrorCallbackFunc: function to call if a fatal error occurs in the serial device reading thread
        :type fatalErrorCallbackFunc: func
        """
        self.alive = False
        self.port = port
        self.baudrate = baudrate

        self._responseEvent = None  # threading.Event()
        self._expectResponseTermSeq = None  # expected response terminator sequence
        self._response = None  # Buffer containing response to a written command
        self._notification = []  # Buffer containing lines from an unsolicited notification from the modem
        # Reentrant lock for managing concurrent write access to the underlying serial port
        self._txLock = threading.RLock()

        self.notifyCallback = notifyCallbackFunc or self._placeholderCallback
        self.fatalErrorCallback = fatalErrorCallbackFunc or self._placeholderCallback

        self.com_args = args
        self.com_kwargs = kwargs

    def connect(self):
        """ Connects to the device and starts the read thread """
        self.serial = serial.Serial(dsrdtr=True, rtscts=False, port=self.port, baudrate=self.baudrate,
                                    timeout=self.timeout, *self.com_args, **self.com_kwargs)
        # Start read thread
        self.alive = True
        self.rxThread = threading.Thread(target=self._readLoop)
        self.rxThread.daemon = True
        self.rxThread.start()

    def close(self):
        """ Stops the read thread, waits for it to exit cleanly, then closes the underlying serial port """
        self.alive = False
        self.rxThread.join()
        self.serial.close()

    def _handleLineRead(self, line, checkForResponseTerm=True):
        # print 'sc.hlineread:',line
        if self._responseEvent and not self._responseEvent.is_set():
            # A response event has been set up (another thread is waiting for this response)
            if self._response is not None:
                self._response.append(line)
            if not checkForResponseTerm or self.RESPONSE_TERM.match(line):
                # End of response reached; notify waiting thread
                # print 'response:', self._response
                self.log.debug('response: %s', self._response)
                self._responseEvent.set()
        else:
            # Nothing was waiting for this - treat it as a notification
            self._notification.append(line)
            if self.serial.in_waiting == 0:
                # No more chars on the way for this notification - notify higher-level callback
                # print 'notification:', self._notification
                self.log.debug('notification: %s', self._notification)
                self.notifyCallback(self._notification)
                self._notification = []

    def _placeholderCallback(self, *args, **kwargs):
        """ Placeholder callback function (does nothing) """

    def _readLoop(self):
        """ Read thread main loop

        Reads lines from the connected device
        """
        try:
            readTermSeq = bytearray(self.RX_EOL_SEQ)
            readTermLen = len(readTermSeq)
            rxBuffer = bytearray()
            while self.alive:
                data = self.serial.read(1)
                if len(data) != 0:  # check for timeout
                    rxBuffer.append(ord(data))
                    if rxBuffer[-readTermLen:] == readTermSeq:
                        # A line (or other logical segment) has been read
                        try:
                            line = rxBuffer[:-readTermLen].decode()
                        except UnicodeDecodeError:
                            line = ''
                        rxBuffer = bytearray()
                        if len(line) > 0:
                            # print 'calling handler'
                            self._handleLineRead(line)
                    elif self._expectResponseTermSeq:
                        if rxBuffer[-len(self._expectResponseTermSeq):] == self._expectResponseTermSeq:
                            line = rxBuffer.decode()
                            rxBuffer = bytearray()
                            self._handleLineRead(line, checkForResponseTerm=False)
            # else:
                # ' <RX timeout>'
        except serial.SerialException as e:
            self.alive = False
            try:
                self.serial.close()
            except Exception:  # pragma: no cover
                pass
            # Notify the fatal error handler
            self.fatalErrorCallback(e)

    @overload
    def write(self, data: str, waitForResponse: Literal[True] = True, timeout: Union[int, float] = 5, expectedResponseTermSeq: Optional[str] = None) -> List[str]:
        ...

    @overload
    def write(self, data: str, waitForResponse: Literal[False], timeout: Union[int, float] = 5, expectedResponseTermSeq: Optional[str] = None) -> None:
        ...

    @overload
    def write(self, data: str, waitForResponse: bool, timeout: Union[int, float] = 5, expectedResponseTermSeq: Optional[str] = None) -> Optional[List[str]]:
        ...

    def write(self, data: str, waitForResponse: bool = True, timeout: Union[int, float] = 5, expectedResponseTermSeq: Optional[str] = None) -> Optional[List[str]]:
        encoded_data = data.encode()
        with self._txLock:
            if waitForResponse:
                if expectedResponseTermSeq:
                    self._expectResponseTermSeq = bytearray(expectedResponseTermSeq.encode())
                self._response = []
                self._responseEvent = threading.Event()
                self.serial.write(encoded_data)
                if self._responseEvent.wait(timeout):
                    self._responseEvent = None
                    self._expectResponseTermSeq = None
                    return self._response
                else:  # Response timed out
                    self._responseEvent = None
                    self._expectResponseTermSeq = None
                    if self._response and len(self._response) > 0:
                        # Add the partial response to the timeout exception
                        raise TimeoutException(self._response)
                    else:
                        raise TimeoutException()
            else:
                self.serial.write(encoded_data)
                return None
