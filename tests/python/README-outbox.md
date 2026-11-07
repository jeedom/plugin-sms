# Outgoing SMS pre-submission retry

Run `python3 -B -m unittest discover -s tests/python` from the plugin root.

The daemon keeps one pending SMS in memory when modem/network preparation fails,
with a maximum of three attempts on successive socket-processing cycles. Later
messages remain queued until this SMS succeeds or reaches its attempt limit.
After the third preparation failure it is discarded with an error log.

Once `sendSms` has been called, an exception has an ambiguous delivery outcome,
including partial delivery of multipart messages. It is logged and never retried.
A successful modem submission does not prove delivery to the recipient.

Pending messages are not persisted across daemon exits/restarts. Existing fatal
modem failures in the main listener loop still stop the daemon. This patch does
not implement reconnects, delivery receipts or a durable outbound queue.

Tests use a fake modem and queue; hardware/network recovery needs live validation.
