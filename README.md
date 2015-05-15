# AroundZOHO
A templated email extractor from GMail and put the data into Zoho as Account and Contact.
Its in PHP.
Two libraries have been used.
The mail IMAP library is bypassed since it wants ext-mbstring and ext-iconv and not all virtual service providers allow these two installed.
You need to customise the fields in Zoho module to use. Someone may use the LEAD module when the first email is recieved. I like to improve this.
Thanks goes to the authors of the valuable two libraries. One for IMAP and another for ZOHO-PHP.


USAGE INSTRUCTIONS:
1. Clone the repo using git
2. Use gmailToZOHO.php for getting data from GMail and setting the data into ZOHO
3. Change BASE_URL relative to your host. Set GMail ID and Password. Set the ZOHO Token.
4. If you are setting a CRON job to automate pulling data from GMail and pushing into ZOHO, you can follow the instruction. Here I've used 10 minutes interval.  10 * * * * php your_path_to_integration/integration/gmailToZOHO.php >/dev/null 2>&1
