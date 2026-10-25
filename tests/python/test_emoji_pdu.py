"""Real PDU encoding checks; no serial dependency, modem, or SMS transmission."""
import importlib
from pathlib import Path
import sys
import types
import unittest

package = types.ModuleType('emoji_test_gsmmodem')
package.__path__ = [str(Path(__file__).resolve().parents[2] / 'resources/smsd/gsmmodem')]
sys.modules[package.__name__] = package
pdu = importlib.import_module(package.__name__ + '.pdu')


class EmojiPduTest(unittest.TestCase):
    def test_known_utf16_bytes(self):
        self.assertEqual(pdu.encodeUcs2('🙂'), bytes.fromhex('D83DDE42'))
        self.assertEqual(pdu.encodeUcs2('🚨'), bytes.fromhex('D83DDEA8'))
        self.assertEqual(pdu.encodeUcs2('é☀€'), bytes.fromhex('00E9260020AC'))

    def test_codec_roundtrip(self):
        for text in ('', 'Température éà ☀', '🚨 Alerte 🙂', '👩🏽\u200d💻 🇫🇷'):
            with self.subTest(text=text):
                data = pdu.encodeUcs2(text)
                self.assertEqual(pdu.decodeUcs2(iter(data), len(data)), text)

    def test_pdu_roundtrip_and_segment_count(self):
        for text, count in (('🙂' * 35, 1), ('🙂' * 36, 2),
                            ('漢' * 70, 1), ('漢' * 71, 2),
                            ('漢' * 66 + '🙂' + '漢' * 3, 2),
                            ('🚨' * 100, 4), ('A' * 160, 1), ('A' * 161, 2)):
            with self.subTest(length=len(text), count=count):
                parts = pdu.encodeSmsSubmitPdu('+33600000000', text)
                self.assertEqual(len(parts), count)
                decoded = [pdu.decodeSmsPdu(str(part)) for part in parts]
                self.assertEqual(''.join(part['text'] for part in decoded), text)
                for part in decoded:
                    if any(ord(char) > 127 for char in part['text']):
                        capacity = 134 if len(parts) > 1 else 140
                        self.assertLessEqual(len(part['text'].encode('utf-16-be')), capacity)

    def test_segment_does_not_split_surrogate_pair(self):
        text = '漢' * 66 + '🙂' + '漢' * 3
        parts = pdu.divideTextUcs2(text)
        self.assertEqual(parts, ['漢' * 66, '🙂' + '漢' * 3])
        self.assertEqual(''.join(parts), text)

    def test_truncated_input_remains_tolerated(self):
        self.assertEqual(pdu.decodeUcs2(iter([0, 65, 0]), 4), 'A')


if __name__ == '__main__':
    unittest.main()
