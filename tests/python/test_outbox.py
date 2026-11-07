import importlib.util
import json
from pathlib import Path
from queue import Queue
import unittest
from unittest.mock import Mock

spec = importlib.util.spec_from_file_location('outbox', Path(__file__).resolve().parents[2] / 'resources/smsd/outbox.py')
outbox = importlib.util.module_from_spec(spec)
spec.loader.exec_module(outbox)


class OutboxTest(unittest.TestCase):
    def setUp(self):
        self.queue = Queue()
        self.modem = Mock()
        self.sender = outbox.SmsOutbox()
        self.add('first')

    def add(self, text, key='test-key'):
        self.queue.put(json.dumps({'apikey': key, 'number': '+33600000000', 'message': text}).encode())

    def process(self):
        self.sender.process(self.queue, self.modem, 'test-key')

    def test_success_is_submitted_once(self):
        self.process()
        self.process()
        self.modem.sendSms.assert_called_once_with('+33600000000', 'first')

    def test_network_recovers_and_order_is_preserved(self):
        self.add('second')
        self.modem.waitForNetworkCoverage.side_effect = [TimeoutError(), None, None]
        self.process()
        self.modem.sendSms.assert_not_called()
        self.assertEqual(self.queue.qsize(), 1)
        self.process()
        self.process()
        self.assertEqual([c.args[1] for c in self.modem.sendSms.call_args_list], ['first', 'second'])

    def test_network_failure_is_bounded_and_next_message_proceeds(self):
        self.add('second')
        self.modem.waitForNetworkCoverage.side_effect = [TimeoutError()] * 3 + [None]
        with self.assertLogs(level='ERROR'):
            for _ in range(3):
                self.process()
        self.assertIsNone(self.sender.pending)
        self.process()
        self.modem.sendSms.assert_called_once_with('+33600000000', 'second')

    def test_submission_error_is_never_retried(self):
        for error in (TimeoutError(), RuntimeError('partial multipart submission')):
            with self.subTest(error=error):
                self.setUp()
                self.modem.sendSms.side_effect = error
                with self.assertLogs(level='ERROR'):
                    self.process()
                self.process()
                self.modem.sendSms.assert_called_once()

    def test_missing_modem_keeps_message_for_later_cycle(self):
        self.sender.process(self.queue, None, 'test-key')
        self.assertIsNotNone(self.sender.pending)
        self.process()
        self.modem.sendSms.assert_called_once()

    def test_invalid_payload_or_key_is_discarded_without_submission(self):
        for raw in (b'broken-json', b'[]', b'\xff', b'{"apikey":"bad"}',
                    b'{"apikey":"test-key","number":[],"message":"test"}'):
            with self.subTest(raw=raw):
                self.queue = Queue()
                self.queue.put(raw)
                with self.assertLogs(level='ERROR'):
                    self.process()
                self.assertIsNone(self.sender.pending)
        self.modem.sendSms.assert_not_called()

    def test_logs_do_not_expose_message_number_or_key(self):
        self.modem.waitForNetworkCoverage.side_effect = RuntimeError('test-key first +33600000000')
        with self.assertLogs(level='WARNING') as logs:
            self.process()
        for private in ('test-key', 'first', '+33600000000'):
            self.assertNotIn(private, '\n'.join(logs.output))


if __name__ == '__main__':
    unittest.main()
