"""Exercise the startup storage blocks without starting the daemon or modem."""
import ast
import importlib.util
from pathlib import Path
import unittest
from unittest.mock import Mock, call

DAEMON = Path(__file__).resolve().parents[2] / 'resources' / 'smsd'
spec = importlib.util.spec_from_file_location('sms_modem_exceptions', DAEMON / 'gsmmodem' / 'exceptions.py')
exceptions = importlib.util.module_from_spec(spec)
spec.loader.exec_module(exceptions)


def storage_code():
    # The daemon parses CLI arguments and starts on import. Execute its actual
    # storage initialization blocks in isolation, preserving both exception paths.
    tree = ast.parse((DAEMON / 'smsd.py').read_text())
    listen = next(node for node in tree.body if isinstance(node, ast.FunctionDef) and node.name == 'listen')
    blocks = []
    for node in ast.walk(listen):
        if not isinstance(node, ast.Try) or not node.body:
            continue
        first = node.body[0]
        if isinstance(first, ast.Expr) and isinstance(first.value, ast.Call):
            args = first.value.args
            if args and isinstance(args[0], ast.Constant) and args[0].value in (
                'AT+CPMS="ME","ME","ME"', 'AT+CPMS="SM","SM","SM"'
            ):
                blocks.append(node)
    assert len(blocks) == 2
    blocks.sort(key=lambda node: node.lineno)
    return compile(ast.Module(body=blocks, type_ignores=[]), str(DAEMON / 'smsd.py'), 'exec')


class StorageLoggingTest(unittest.TestCase):
    def run_storage(self, errors):
        modem, log = Mock(), Mock()
        modem.write.side_effect = errors
        exec(storage_code(), {'gsm': modem, 'logging': log, 'CmsError': exceptions.CmsError})
        return modem, log

    def test_cms321_on_selection_skips_me_purge_and_continues_with_sim(self):
        modem, log = self.run_storage([exceptions.CmsError('AT+CPMS', 321), None, None])
        self.assertEqual(modem.write.call_args_list, [
            call('AT+CPMS="ME","ME","ME"'), call('AT+CPMS="SM","SM","SM"'), call('AT+CMGD=1,4')])
        log.debug.assert_called_once()
        log.error.assert_not_called()

    def test_cms321_on_me_purge_is_debug_and_sim_still_processed(self):
        modem, log = self.run_storage([None, exceptions.CmsError('AT+CMGD', 321), None, None])
        self.assertEqual(modem.write.call_count, 4)
        log.debug.assert_called_once()
        log.error.assert_not_called()

    def test_other_errors_remain_errors(self):
        for error in (exceptions.CmsError('AT+CPMS', 500), exceptions.CmeError('AT+CPMS', 321), RuntimeError('CMS 321')):
            with self.subTest(error=error):
                _, log = self.run_storage([error, None, None])
                log.error.assert_called_once_with("Exception on 'ME': %s", error)
                log.debug.assert_not_called()

    def test_sim_cms321_remains_an_error(self):
        error = exceptions.CmsError('AT+CPMS', 321)
        _, log = self.run_storage([None, None, error])
        log.error.assert_called_once_with("Exception on 'SM': %s", error)
        log.debug.assert_not_called()

    def test_success_does_not_log_an_error(self):
        modem, log = self.run_storage([None] * 4)
        self.assertEqual(modem.write.call_count, 4)
        log.error.assert_not_called()
        log.debug.assert_not_called()


if __name__ == '__main__':
    unittest.main()
