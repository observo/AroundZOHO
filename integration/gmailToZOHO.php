<?php
	//ADDING HEADER TO APPROPRIATE MIME-TYPE AND CONTENT-TYPE
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	
	//DEFINING BASE URL RELATIVE TO HOSTING
	define('BASE_URL', '/var/www/html/');

	//GMAIL OPTIONS
	define('EMAIL_HOSTNAME', '{imap.gmail.com:993/imap/ssl}INBOX');
	define('EMAIL_USERNAME', 'your_email@gmail.com');
	define('EMAIL_PASSWORD', 'email_password');
    
	//AUTOLOADING OF CLASSES. THIS IS PSR-0 COMPLIANT.
	require_once(BASE_URL.'mail/vendor/autoload.php');
    	require_once(BASE_URL.'mail/TextParserClass.php');
    	require_once(BASE_URL.'zohocrm-php/src/autoload.php');
    
	//SET TIMEZONE. HERE ITS UTC.
	date_default_timezone_set('UTC');

	//ZOHO CRM INITIALIZATION
	$client=new ZohoCRM\Client('auth_token_from_zoho');
    	$params=array();
    	$params['duplicateCheck'] = 2;
    
	//GMAIL IMAP INBOX RETRIEVED
	$inbox = imap_open(EMAIL_HOSTNAME,EMAIL_USERNAME,EMAIL_PASSWORD) or die('Cannot connect to Gmail: ' . imap_last_error());
	$date = date("d M Y", strToTime ( "-1 days" ));
	$emails = imap_search($inbox, "SINCE \"$date\"", SE_UID);
	if($emails) {
	    rsort($emails);
	    foreach($emails as $email_number) {
        	$overview = imap_fetch_overview($inbox,$email_number,0);
        	$message = imap_fetchbody($inbox,$email_number,1);
		//THIS IS FOR VERY SPECIFIC REASON.
		$smsAddress='donotreply@example.com';
        	$accountData=array();
        	$contactData=array();
        	$emailArrivalDateTime=new DateTime($overview[0]->date);
        	$emailArrivalDateTime=$emailArrivalDateTime->format('Y-m-d H:i:s');
		$contactData['Email Arrival Time']=$emailArrivalDateTime;
        	$fromAddress=$overview[0]->from;
        	$textToParse = strip_tags($message);
		try{
            		$parser = new TextParser(BASE_URL.'mail/templates');
            		$parsedContact=$parser->parseText($textToParse);
           		$parsedContact=array_map('trim', $parsedContact);
        	}catch (Exception $e) {
            		echo '<b>Error:</b> ' . $e->getMessage();
        	}
        	if($fromAddress==$smsAddress){
            		$contactData['Lead Source']='Phone Call';
            $inDateTime=explode(' ', $parsedContact['in'], 3);
            if(!empty($inDateTime)){
                                $inDate=strtolower($inDateTime[0]);
                                $inDate = DateTime::createFromFormat('dMy', $inDate);
                                //$contactData['inDate']=$inDate->format('Y-m-d');
                                if(!empty($inDateTime[1])){
                                        $inTime=$inDateTime[1];
                                        $inTimeHour=substr($inTime, 0, 2);
                                        $inTimeMinute=substr($inTime, 2);
                                        $contactData['Contact Time']=$inDate->format('Y-m-d').' '.$inTimeHour.':"'.$inTimeMinute.':'.'00';
                                }else{
                                        $contactData['Contact Time']=$inDate->format('Y-m-d').' 00:00:00';
                                }
                        }


            $contactData['for']=$parsedContact['for'];
                        $firstNameLastName=explode(' ', $parsedContact['from'], 2);
                        if(trim($firstNameLastName[1])==''){
                                $contactData['Last Name']=$firstNameLastName[0];
                        }else{
                                $contactData['First Name']=$firstNameLastName[0];
                                $contactData['Last Name']=$firstNameLastName[1];
                        }
            if($parsedContact['company']!=''){
                                //create Account and put this data into Account Name
                                $accountData['Account Name']=$parsedContact['company'];
                                $accountData['Phone']=$parsedContact['tel'];

                                $contactData['Account Name']=$parsedContact['company'];
                        }
			$contactData['Email']=$parsedContact['from'];
            $contactData['Phone']=$parsedContact['tel'];
            $callerIDDescription=explode(' ', $parsedContact['callerID'], 2);
            $contactData['Caller ID']=$callerIDDescription[0];
            $contactData['Description']=$callerIDDescription[1];

        }else{
            if(strpos($textToParse, 'Company Information') !== false){
                $contactData['Lead Source']='Apply Now Form';
                //this from field is commented out on the assumption that from and name are same.
                                //$contactData['from']=$parsedContact['from'];
                                $firstNameLastName=explode(' ', $parsedContact['name'], 2);
                                if(trim($firstNameLastName[1])==''){
                                        $contactData['Last Name']=$firstNameLastName[0];
                                }else{
                                        $contactData['First Name']=$firstNameLastName[0];
                                        $contactData['Last Name']=$firstNameLastName[1];
                                }
                $contactData['Email']=$parsedContact['email'];
                $contactData['Phone']=$parsedContact['phone'];
                                if($parsedContact['company']!=''){

                                        $accountData['Account Name']=$parsedContact['company'];
                                        $accountData['Phone']=$parsedContact['phone'];
                                        $accountData['Website']=$parsedContact['companyWebsite'];
                                        $accountData['Amount of Funding Needed']= $parsedContact['amountOfFundingNeeded'];
                                        $accountData['Personal Credit Score']=$parsedContact['personalCreditScore'];
                                        $accountData['Time in Business']=$parsedContact['timeInBusiness'];
                                        $accountData['Annual Sales']=$parsedContact['annualSales'];

                                        $contactData['Account Name']=$parsedContact['company'];
                                }
                $contactData['Description']=$parsedContact['message'];
        }else{
                $contactData['Lead Source']='Contact Us Form';
                //$contactData['from']=$parsedContact['from'];
                                $firstNameLastName=explode(' ', $parsedContact['from'], 2);
                                if(trim($firstNameLastName[1])==''){
                                        $contactData['Last Name']=$firstNameLastName[0];
                                }else{
                                        $contactData['First Name']=$firstNameLastName[0];
                                        $contactData['Last Name']=$firstNameLastName[1];
                                }
				$contactData['Email']=$parsedContact['from'];
                $contactData['Phone']=$parsedContact['phone'];
                $contactData['Description']=$parsedContact['message'];
            }
        }
        ob_start();
                $accountData=array_filter($accountData);
                $contactData=array_filter($contactData);
                var_dump($accountData);
                var_dump($contactData);
                if(!empty($accountData)){
                        $client->setModule('Accounts');
                        $data=array();
                        $data["records"][]=$accountData;
                        //THe NEXT  LINE IS FOR ZOHO ACCOUNTS MODULE INSERTION
                        $res=$client->insertRecords($data, $params);
                        var_dump($res);
                }
                if(!empty($contactData)){
                        $client->setModule('Contacts');
                        $data=array();
                        $data["records"][]=$contactData;
                        //THe NEXT  LINE IS FOR ZOHO CONTACTS MODULE INSERTION
                        $res=$client->insertRecords($data, $params);
                        var_dump($res);
                }

                $outStringVar = ob_get_contents() ;
                $fp=fopen('mm.txt','a+');
                fwrite($fp, $outStringVar );
                fclose($fp);
                ob_end_clean();
	
    }
	}
	imap_close($inbox);
?>
