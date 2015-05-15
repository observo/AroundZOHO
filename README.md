# GMail-Zoho
A templated email extractor from GMail and put the data into Zoho as Account and Contact.
Its in PHP.
Two libraries have been used.
The mail IMAP library is bypassed since it wants ext-mbstring and ext-iconv and not all virtual service providers allow these two installed.
You need to customise the fields in Zoho module to use. Someone may use the LEAD module when the first email is recieved. I like to improve this.
Thanks goes to the authors of the valuable two libraries. One for IMAP and another for ZOHO-PHP.
