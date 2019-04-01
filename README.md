# MailViewer

Minimalistic online tool to view a raw email as plain text or HTML. Basically it uses PHP's `quoted_printable_decode()` function ( http://php.net/manual/en/function.quoted-printable-decode.php ) to decode the encoded raw mail text plus some ugly code to find the parts (plain text, HTML, ...) of the mail. Consider this being like a result of a hackathon - it probably works in most scenarios but there are no tests and the feature set is rudimentary.

![Screenshot of MailViewer](https://abload.de/img/mailviewerfkjif.png)

### Note

Uses Framy CSS framework ( https://github.com/aaroniker/framy-css )
