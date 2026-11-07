"""Bounded retries before SMS submission; never retry an ambiguous submission."""
import json
import logging
from queue import Empty


class SmsOutbox:
    def __init__(self, max_attempts=3):
        self.pending = None
        self.attempts = 0
        self.max_attempts = max_attempts

    def process(self, queue, modem, apikey):
        if self.pending is None:
            try:
                raw = queue.get_nowait()
            except Empty:
                return
            try:
                message = json.loads(raw.decode('utf-8'))
                if not isinstance(message, dict) or message.get('apikey') != apikey:
                    logging.error('Invalid SMS socket authentication')
                    return
                if any(not isinstance(message.get(key), str) or not message[key].strip()
                       for key in ('number', 'message')):
                    logging.error('Invalid SMS socket payload')
                    return
            except (ValueError, UnicodeError, AttributeError):
                logging.error('Invalid SMS socket payload')
                return
            self.pending = message
            self.attempts = 0

        self.attempts += 1
        try:
            if modem is None:
                raise RuntimeError('Modem unavailable')
            modem.waitForNetworkCoverage()
        except Exception:
            if self.attempts < self.max_attempts:
                logging.warning('SMS not submitted: modem/network unavailable; attempt %d/%d, retry next cycle',
                                self.attempts, self.max_attempts)
            else:
                logging.error('SMS not submitted: modem/network unavailable after %d attempts; message discarded',
                              self.attempts)
                self.pending = None
            return

        message = self.pending
        # Submission can partially succeed (including multipart SMS) before an
        # exception is raised. Retrying here could deliver duplicate messages.
        self.pending = None
        try:
            modem.sendSms(message['number'], message['message'])
            logging.info('SMS submitted to modem')
        except Exception:
            logging.error('SMS submission failed; delivery outcome unknown, automatic retry disabled to avoid duplicates')
