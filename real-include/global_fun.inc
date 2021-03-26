<?php 
	require_once("html_tls_functions.php");
    require("html2text.inc");
    require("secureurl.php"); //For Encoding the URL Bug No:3174

	/**
	* This variable is for Global time difference offset value.
	* Use this when a particular column has date value only.
	* the below function is intended for same purpose.
	**/

	define("TIMEZONE_DIFF_TIME","01:00:00");


	// Get last logged in, last accessed time from userlog_acc table 
	function getLastLogin()
	{
		global $maildb,$db,$username;

		$que = "SELECT DATE_FORMAT( MAX( last_login ) , '%W %m/%d/%Y %h:%i %p' ) , DATE_FORMAT( MAX( last_access ) , '%W %m/%d/%Y %h:%i %p' ) FROM userlog_acc WHERE username = '$username' GROUP BY username";
		$res = mysql_query($que,$db);
		$logacc = mysql_fetch_row($res);
		$logd =  $logacc[0]."|".$logacc[1];

		return $logd;
	}

	// Insert into userlog_acc table, last accessed time
	function insUserLogin()
	{
		global $maildb,$db,$username;

		$que = "INSERT INTO userlog_acc VALUES ( '', '$username', NOW( ) , NOW( ) )";
		mysql_query($que,$db);
		$sesinsid = mysql_insert_id($db);
		return $sesinsid;
	}

	function getTimezone()
	{
		global $maildb,$db,$username;

		$que = "SELECT timezone FROM orgsetup WHERE userid = '$username'";
		$res = mysql_query($que,$db);
		if(mysql_num_rows($res)>0)
		{
			$TimezoneSno = mysql_fetch_row($res);
			$TZque = "SELECT phpvar,time,timezone FROM timezone WHERE sno = '".$TimezoneSno[0]."'";
			$TZres = mysql_query($TZque,$db);
			if(mysql_num_rows($TZres)>0)
			{
				$TimezoneDet= mysql_fetch_row($TZres);
				$user_timezone=array($TimezoneDet[0],$TimezoneDet[0],$TimezoneDet[2]);
			}
			else
			{
				$user_timezone=array("EST5EDT","EST5EDT","Eastern Time");
			}
		}
		else
		{
			$user_timezone=array("EST5EDT","EST5EDT","Eastern Time");
		}
		return $user_timezone;
	}

	function checkDuplicateSession()
	{
		global $maildb,$db, $username;

		$duplicate = false;

		$sess_id=session_id();
		$host = str_replace("cmail","appserver",$_SERVER["HTTP_HOST"]);

		$sque = "SELECT usertype FROM users WHERE username = '$username'";
		$sres = mysql_query($sque,$db);
		$srow = mysql_fetch_row($sres);
		if(trim($srow[0])!="")
		{
			$cque = "SELECT COUNT(1) FROM users_session WHERE username = '$username' AND active='Y'";
			$cres = mysql_query($cque,$db);
			$crow = mysql_fetch_row($cres);
			if($crow[0] > 0)
			{
				$aque = "SELECT sno FROM users_session WHERE username = '$username' AND active = 'Y' AND sessionid='$sess_id'";
				$ares = mysql_query($aque,$db);

				if(mysql_num_rows($ares)>0)
				{
					$arow = mysql_fetch_row($ares);

					$uque = "UPDATE users_session SET host='$host', sessiontime = ".time()." WHERE sno=".$arow[0];
					mysql_query($uque,$db);
				}
				else
				{
					$duplicate = true;
				}
			}
			else
			{
				$ique = "INSERT INTO users_session (username,host,sessionid,sessiontime,active) VALUES ('$username','$host','$sess_id','".time()."','Y')";
				mysql_query($ique,$db);
			}
		}

		return $duplicate;
	}

	function swapUserSession()
	{
		global $maildb,$db, $username;

		$sess_id=session_id();
		$host = str_replace("cmail","appserver",$_SERVER["HTTP_HOST"]);

		$sque = "SELECT usertype FROM users WHERE username = '$username'";
		$sres = mysql_query($sque,$db);
		$srow = mysql_fetch_row($sres);
		if(trim($srow[0])!="")
		{
			$aque = "SELECT sno FROM users_session WHERE username = '$username' AND active = 'Y' AND sessionid='$sess_id'";
			$ares = mysql_query($aque,$db);
			if(mysql_num_rows($ares)>0)
			{
				$arow = mysql_fetch_row($ares);

				$uque = "UPDATE users_session SET host='$host', sessiontime = ".time()." WHERE sno=".$arow[0];
				mysql_query($uque,$db);
			}
			else
			{
				$uque = "UPDATE users_session SET active = 'N' WHERE username = '$username' AND active = 'Y'";
				mysql_query($uque,$db);

				$ique = "INSERT INTO users_session (username,host,sessionid,sessiontime,active) VALUES ('$username','$host','$sess_id','".time()."','Y')";
				mysql_query($ique,$db);
			}
		}
	}

	function checkUserSession()
	{
		global $maildb,$db, $username, $default_locked_link, $xajax, $Ajaxjunktimestump;

		$logout=date("Y-m-d H:i:s");
		$sess_id=session_id();
		$host = str_replace("cmail","appserver",$_SERVER["HTTP_HOST"]);

		$sque = "SELECT usertype FROM users WHERE username = '$username'";
		$sres = mysql_query($sque,$db);
		$srow = mysql_fetch_row($sres);

		if(trim($srow[0])!="")
		{
			$cque = "SELECT COUNT(1) FROM users_session WHERE active='Y' AND host='$host' AND username = '$username' AND sessionid='$sess_id'";
			$cres = mysql_query($cque,$db);
			$crow = mysql_fetch_row($cres);
			if($crow[0] == 0)
			{
				$que="update users set last_login='$logout' where username='$username'";
				mysql_query($que,$db);

				$uque = "UPDATE users_session SET active = 'S' WHERE host='$host' AND sessionid = '$sess_id' AND username='$username'";
				mysql_query($uque,$db);

				$session_dir=ini_get("session.save_path");
				unlink($session_dir."/sess_".$sess_id);

				delFolder($WDOCUMENT_ROOT);
				clearCookies($_COOKIE,"");
				foreach($_SESSION as $key=>$value)
					unset($_SESSION[$key]);

				session_unset();

				if($wintype=="wifr" )
				{
					$wintype=(trim($wintype)!="") ? $wintype : "window";
					echo "<script>alert('AK0003: Oops! This session has ended. Your AkkenCloud\u2122 account has been logged in from another computer/browser.'); top.window.location.href='".$default_locked_link."';</script>";
				}
				else if(trim($xajax)=="gridData" || $Ajaxjunktimestump!="")
				{
					echo '^^^***GridAjaxSessionDuplicate***^^^|'.$default_locked_link;
				}
				else
    	    	{
					echo "<script>
					alert('AK0003: Oops! This session has ended. Your AkkenCloud\u2122 account has been logged in from another computer/browser.');
					try
					{
						if(!window.opener.location)
						{
							window.location.href= '".$default_locked_link."';
						}
						else
						{
							window.opener.location.href = '".$default_locked_link."';
							window.close();
						}
					}
					catch(oException)
					{
						window.location.href= '".$default_locked_link."';
					}
					</script>";
				}
				exit();
			}
		}
	}

	function finalPreference($pref)
	{
		$final = "NO";
		if($pref!="NO")
		{
			$spref = explode("+",$pref);
			for($i=1;$i<count($spref);$i++)
			{
				if($spref[$i]!="" && $spref[$i]!="-")
				{
					$final = $pref."+";
					break;
				}
			}
		}

		return $final;
	}

	function isSuperUser($emp_id)
	{
		global $maildb,$db;

		$que="select type from users where username='$emp_id'";
		$res=mysql_query($que,$db);
		$row=mysql_fetch_row($res);

		if($row[0]=="sp")
			return TRUE;
		else
			return FALSE;
	}
	//This funtion is to check the user preference
	function chkUserPref($pref,$chkval)
	{
		$arr = explode("+",$pref);

		if(in_array($chkval,$arr))
			return true;
		else
			return false;
	}

	//this function is holding an array of different date formats
	function setMySQLDateFormat()
	{
		$array_mysql_dtformats = array (
		"Date"  => array("-" => "%m-%d-%Y","/" => "%m/%d/%Y"),
		"ShYear" => array("-" => "%m-%d-%y","/" => "%m/%d/%y"),
		"Time"  => array("M" => "%h:%i %p","24" => "%H:%i"),
		"Timesec"  => array("M" => "%h:%i:%s %p","24" => "%H:%i:%s"),
		"ShMonth" => array("-" => "%b %d %Y","/" => "%b %d %Y"),
		"YMDDate" => array("-" => "%Y-%m-%d","/" => "%Y/%m/%d"),
		"FMonth" => "%M %D, %Y",
		"MonYear" => array("-" => "%m-%Y","/" => "%m/%Y"),
		"WeekDay" => array("-" => "%u","/" => "%u"),
		"DMYDate" => array("-" => "%d-%m-%Y","/" => "%d/%m/%Y"),
		"MDYWeek" => array("-" => "%m-%d-%Y (%W)","/" => "%m/%d/%Y (%W)"),
		"DateDay" => array("-" => "%W","/" => "%W"),
		"CEYIntDate"  => array("-" => "%c-%e-%Y","/" => "%c/%e/%Y"),
		"Day" => array("-" => " %W ","/" => " %W ")
									   );
		return $array_mysql_dtformats;
	}
	$Mysql_DtFormat = setMySQLDateFormat();
	//function ends here

	function getSubDomainEmail()
	{
		global $maildb,$db,$username,$domainname;

		$que1="select emailuser from EmailAcc where username='".$username."' and TYPE='R'";
	    $res1=mysql_query($que1,$db);
	    $row1=mysql_fetch_row($res1);
	    $emailuser=$row1[0];

	    return trim($emailuser)."@".$domainname;
	}

	function getFromEmailID($pre_user)
	{
		global $companyuser,$domainname,$maildb,$db;

		$sqlToEmailId = "SELECT CONCAT_WS('','donot-reply',SUBSTRING(em.mailid,instr(em.mailid,'@'),length(em.mailid))) FROM external_mail em,users us WHERE em.username=us.username AND em.deflt='Yes' AND us.type='sp'";
		$resToEmailId = mysql_query($sqlToEmailId,$db);
		$extNum = mysql_num_rows($resToEmailId);

		if($extNum > 0)
		{
			$emailExternal = mysql_fetch_row($resToEmailId);
			$fromID = $emailExternal[0];
		}
		else
		{
			$fromID = "donot-reply@".$domainname;
		}
		return $fromID;
	}

	function tzRetQueryStringDTime($tbl_col_name,$dt_type,$separator)
	{
		/*
		This function is used only when input(i.e $tbl_col_name) should evaluate to ex :"yyyy-mm-dd hh:ii:ss"
		*/
		global $user_timezone;
		$TzConvert="CONVERT_TZ(".$tbl_col_name.",'SYSTEM','".$user_timezone[1]."')";
		return  "DATE_FORMAT(".$TzConvert.",'".getMySQLDateFormat($dt_type,$separator)."')";

	}

	function tzRetQueryStringDate($tbl_col_name,$dt_type,$separator)
	{
		/*
		 This function is used only when input(i.e $tbl_col_name) should evaluate to ex :"yyyy-mm-dd"
		*/
		global $user_timezone;

		 return "DATE_FORMAT(".$tbl_col_name.",'".getMySQLDateFormat($dt_type,$separator)."')";
	}

	function tzEmailSent($tbl_col_name)
	{
		/*
         This function is used only when reply/forward to prepare the sent date of received email.
        */

		global $user_timezone;
		return "DATE_FORMAT(CONVERT_TZ(FROM_UNIXTIME(".$tbl_col_name."),'SYSTEM','".$user_timezone[1]."'),'%W, %M %d, %Y %l:%i %p')";
	}

	function tzActEmailSent($tbl_col_name)
	{
		/*
         This function is used only when reply/forward to prepare the sent date of received email from Activiites.
        */

		global $user_timezone;
		return "DATE_FORMAT(CONVERT_TZ(".$tbl_col_name.",'SYSTEM','".$user_timezone[1]."'),'%W, %M %d, %Y %l:%i %p')";
	}

	function tzRetQueryStringSelBoxDate($tbl_col_name,$dt_type,$separator)
	{
		/*
         This function is used only when input(i.e $tbl_col_name) should evaluate to ex :"yyyy-mm-dd"
        */
        global $user_timezone;

        return  "DATE_FORMAT(".$tbl_col_name.",'".getMySQLDateFormat($dt_type,$separator)."')";
	}

	function tzRetQueryStringSTRTODate($tbl_col_name,$cur_format,$dt_type,$separator)
	{
		/*
         This function is used only when input(i.e $tbl_col_name) should evaluate to ex :"yyyy-mm-dd"
        */
        global $user_timezone;
		return  "DATE_FORMAT(STR_TO_DATE(IF(".$tbl_col_name."='0-0-0','00-00-0000',".$tbl_col_name."),'".$cur_format."'),'".getMySQLDateFormat($dt_type,$separator)."')";
	}

	function tzRetQueryStringSTRTOMysqlDate($tbl_col_name,$cur_format,$dt_type,$separator)
	{
		/*
         This function is used only when input(i.e $tbl_col_name) should evaluate to ex :"yyyy-mm-dd"
        */
        global $user_timezone;
		return  "DATE_FORMAT(STR_TO_DATE(IF(".$tbl_col_name."='0-0-0' || ".$tbl_col_name."='','0000-00-00',".$tbl_col_name."),'".$cur_format."'), '%Y-%m-%d')";
	}

	/**
	* The following function returns two types of Return values
	* One is  UnixTimestamp values of current system date before and after timezone formatting.
	* The other is Simple DateTime Format values of current system date before and after timezone formatting.
	**/
	function mySQLNowDate($time_type)
	{
		global $maildb,$db;
		//If the input parameter is "Unix", then it returns UnixTimestamp values of current system date before and after timezone formatting.
		if($time_type=="Unix")
		{
			$time_qry = "SELECT UNIX_TIMESTAMP(".tzRetQueryStringDTime('now()','YMDDateTime24Sec','-')."),UNIX_TIMESTAMP()";
		}
		//If the input parameter is not "Unix", then it returns Simple DateTime Format values of current system date before and after timezone formatting.
		else
		{
		 	$time_qry = "SELECT ".tzRetQueryStringDTime('now()','YMDDateTime24Sec','-').",now()";
		}
		$ret_res = mysql_query($time_qry,$db);
		$ret_info = mysql_fetch_row($ret_res);
		return $ret_info;
	}
	function getMySQLDateFormat($dt_type,$separator='/')
	{
		global $Mysql_DtFormat;
		$array_DateFormats["Date"] = array("YMDDate","MonYear","Date","WeekDay","DMYDate","MDYWeek","ShMonth","Day","CEYIntDate");
		$array_DateFormats["DateTime"] = array("DateTime","DateTimeSec");
		$array_DateFormats["DateTime24"] = array("DateTime24","DateTime24Sec","DateTime24Day");
		$array_DateFormats["YMDDateTime"] = array("YMDDateTime24Sec","YMDDateTimeSec");


		if(in_array($dt_type,$array_DateFormats["DateTime"]))
		 	$retFrmt=$Mysql_DtFormat["Date"][$separator]." ".($dt_type=="DateTimeSec"?$Mysql_DtFormat["Timesec"]["M"]:$Mysql_DtFormat["Time"]["M"]);
		else if(in_array($dt_type,$array_DateFormats["DateTime24"]))
		 	$retFrmt=$Mysql_DtFormat["Date"][$separator]." ".($dt_type=="DateTime24Sec"?$Mysql_DtFormat["Timesec"]["24"]:$Mysql_DtFormat["Time"]["24"])." ".($dt_type=="DateTime24Day"?$Mysql_DtFormat["DateDay"][$separator]:"");
		else if($dt_type=="DateDay")
			$retFrmt=$Mysql_DtFormat["Date"][$separator]." ".$Mysql_DtFormat["DateDay"][$separator];
		else if(in_array($dt_type,$array_DateFormats["YMDDateTime"]))
			$retFrmt=$Mysql_DtFormat["YMDDate"][$separator]." ".($dt_type=="YMDDateTime24Sec"?$Mysql_DtFormat["Timesec"]["24"]:$Mysql_DtFormat["Timesec"]["M"]);
		else if(in_array($dt_type,$array_DateFormats["Date"]))
		  	$retFrmt=$Mysql_DtFormat[$dt_type][$separator];
		return $retFrmt;
	}
	//functions related to Timezones ends here

	//to get the manage name
	function getManage($sno)
	{
		global $maildb,$db;

		if($sno)
		{
            $manage_sql="select name from manage where sno='$sno'";
            $manage_res=mysql_query($manage_sql,$db);
            $manage_Data=mysql_fetch_row($manage_res);
		}
		return $manage_Data[0];
	}

	//to get the country name
	function getCountry($cid)
	{
		global $maildb,$db;
		if($cid)
		{
		$con_que="select country from countries where sno='$cid'";
		$res_que=mysql_query($con_que,$db);
		$res_data=mysql_fetch_row($res_que);
		}
		return $res_data[0];
	}
	function getmanagename($cid)
	{
		global $maildb,$db;
		if($cid)
		{
		$con_que="select name from manage where sno=$cid and type='interviewstatus'";
		$res_que=mysql_query($con_que,$db);
		$res_data=mysql_fetch_row($res_que);
		}
		return $res_data[0];
	}

	function getCountryCode($cname)
	{
		global $db;
		if($cname)
		{
			$con_que="select sno from countries where country='$cname' OR country_abbr='$cname'";
			$res_que=mysql_query($con_que,$db);
			$res_data=mysql_fetch_row($res_que);
			$num=mysql_num_rows($res_que);
		}

		if($num==0)
			return 0;
		else
			return $res_data[0];
	}

	//to get the Contact name
	function getContactName($contid)
	{
		global $maildb,$db;
		if($contid)
		{
		$cont_sql="select concat_ws(' ',fname,mname,lname) from staffoppr_contact where sno=$contid";
		$cont_res=mysql_query($cont_sql,$db);
		$cont_data=mysql_fetch_row($cont_res);
		}
		return $cont_data[0];
	}

    //Function to check the emailid for the respective domain name
	function getContactDomain($contmail)
    {
        global $maildb,$db,$domainname,$username;

        $contactaccess="ALL";

        if($contmail!="")
        {
            $que="SELECT count(1) FROM EmailAcc a LEFT JOIN external_mail b ON a.username = b.username WHERE concat(a.emailuser, '@', '".$domainname."') = '".$contmail."' OR b.mailid='".$contmail."'";

    		$res=mysql_query($que,$db);
    		$numrows=mysql_fetch_array($res);

    		if($numrows[0]>0)
                $contactaccess=$username;
            else
                $contactaccess="ALL";
        }
        return $contactaccess;
    }

	//get the super parent of the company
	function getSupParent($cmpsno)
	{
		 global $maildb,$db,$rootNode;
		 $super_sql="select parent from staffoppr_cinfo where sno=$cmpsno";
		 $super_res=mysql_query($super_sql,$db);
		 $super_data=mysql_fetch_row($super_res);

		 if($super_data[0]!=0 && $super_data[0]!='')
		 {
		     getSupParent($super_data[0]);
		 }
		 else
		 {
		    $rootNode=$cmpsno;
		 }
	}

	//to get the divisions in Job Order..new
	function JobOrderDivTree($par,$move,$curSno,$source_Page)
	{
		global $maildb,$db,$username;
		$Sql="select sno,address1,address2,city,state,country,zip,IF(accessto='ALL','Public',IF(accessto='$username','Private',IF(FIND_IN_SET('$username',accessto)>0,'Share','None'))),status,cname from staffoppr_cinfo where  parent='".$par."' order by cname";
		$Res=mysql_query($Sql,$db);
		$Tot_Cols=mysql_num_fields($Res);
		while($Data=mysql_fetch_row($Res))
		{
			$CheckChild_sql="select sno from staffoppr_cinfo where parent=$Data[0]";
			$CheckChild_res=mysql_query($CheckChild_sql,$db);
			$checkrows=mysql_num_rows($CheckChild_res);
			if($checkrows>0)
				$Icon='/BSOS/images/crm/icon-div.gif';
			else
			$Icon='/BSOS/images/crm/icon-branch.gif';
			$divAddr='';
			$divAddr=getCompDivisionAddress($Data[9],$Data[1],$Data[2],$Data[3],$Data[4],getCountry($Data[5]),$Data[6]);

			if($Data[0]==$curSno)
				{
					$put_var='&nbsp;&nbsp;&nbsp;&nbsp;';
					$DispData="&nbsp;<font  class='crmsummary-contentbolddata1'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
			 	}
			 else
			  {
				   $put_var = "<a class=remind-delete-align href=javascript:del_divs('$Data[0]','$par','$source_Page')><img src='/BSOS/images/crm/icon-delete.gif' width=10 height=9 alt='' border=0 align=left></a>";

				  if($Data[7]!='None')
				    {
					if($Data[8]=='INACTIVE')
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/hisreviewmanage.php?addr=$Data[0]','compArchieve',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
					else
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/viewcompanySummary.php?addr=$Data[0]','',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
				    }
				   else
				   {
						$DispData="&nbsp;<font class='crmsummary-lnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
				   }
			}


			echo $put_var.$move."<img src='/BSOS/images/email/L.png' valign=top border=0><img src='$Icon' valign=middle border=0>".$DispData."</font><br/>";
			if($checkrows>0)
				JobOrderDivTree($Data[0],$move."&nbsp;&nbsp;&nbsp;&nbsp;",$curSno,'$source_Page');

		}//while
	}

	//to get the divisions in Job Order..new
	function JobOrder_editTree($par,$move,$curSno,$source_Page)
	{
		global $maildb,$db,$username;
		$Sql="select sno,address1,address2,city,state,country,zip,IF(accessto='ALL','Public',IF(accessto='$username','Private',IF(FIND_IN_SET('$username',accessto)>0,'Share','None'))),status,cname from staffoppr_cinfo where  parent='".$par."' order by cname";
		$Res=mysql_query($Sql,$db);
		$Tot_Cols=mysql_num_fields($Res);
		while($Data=mysql_fetch_row($Res))
		{
			$CheckChild_sql="select sno from staffoppr_cinfo where parent=$Data[0]";
			$CheckChild_res=mysql_query($CheckChild_sql,$db);
			$checkrows=mysql_num_rows($CheckChild_res);
			if($checkrows>0)
				$Icon='/BSOS/images/crm/icon-div.gif';
			else
			$Icon='/BSOS/images/crm/icon-branch.gif';
			$divAddr='';
			$divAddr=getCompDivisionAddress($Data[9],$Data[1],$Data[2],$Data[3],$Data[4],getCountry($Data[5]),$Data[6]);
			if($Data[0]==$curSno)
				{
					$put_var='&nbsp;&nbsp;&nbsp;&nbsp;';
					$DispData="&nbsp;<font  class='crmsummary-contentbolddata1'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
			 	}
			 else
			  {
				   $put_var = "<a class=remind-delete-align href=javascript:del_editdivs('$Data[0]','$curSno','$source_Page')><img src='/BSOS/images/crm/icon-delete.gif' width=10 height=9 alt='' border=0 align=left></a>";

				  if($Data[7]!='None')
				    {
					if($Data[8]=='INACTIVE')
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/hisreviewmanage.php?addr=$Data[0]','compArchieve',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
					else
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/viewcompanySummary.php?addr=$Data[0]','',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
				    }
				   else
				   {
						$DispData="&nbsp;<font class='crmsummary-lnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
				   }
			}


			echo $put_var.$move."<img src='/BSOS/images/email/L.png' valign=top border=0><img src='$Icon' valign=middle border=0>".$DispData."</font><br/>";
			if($checkrows>0)
				JobOrder_editTree($Data[0],$move."&nbsp;&nbsp;&nbsp;&nbsp;",$curSno,$source_Page);

		}//while
	}


	//to get the divisions tree
	function divTree($par,$move,$curSno)
	{
		global $maildb,$db,$username;
		$Sql="select sno,address1,address2,city,state,country,zip,IF(accessto='ALL','Public',IF(accessto='$username','Private',IF(FIND_IN_SET('$username',accessto)>0,'Share','None'))),status,cname from staffoppr_cinfo where  parent='".$par."' order by cname";
		$Res=mysql_query($Sql,$db);
		$Tot_Cols=mysql_num_fields($Res);
		while($Data=mysql_fetch_row($Res))
		{
			$CheckChild_sql="select sno from staffoppr_cinfo where parent=$Data[0]";
			$CheckChild_res=mysql_query($CheckChild_sql,$db);
			$checkrows=mysql_num_rows($CheckChild_res);
			if($checkrows>0)
				$Icon='/BSOS/images/crm/icon-div.gif';
			else
			$Icon='/BSOS/images/crm/icon-branch.gif';
			$divAddr='';
			$divAddr=getCompDivisionAddress($Data[9],$Data[1],$Data[2],$Data[3],$Data[4],getCountry($Data[5]),$Data[6]);

			if($Data[0]==$curSno)
				{
					$put_var='&nbsp;&nbsp;&nbsp;&nbsp;';
					$DispData="&nbsp;<font  class='crmsummary-contentbolddata1'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
			 	}
			 else
			  {
				   $put_var = "<a class=remind-delete-align href=javascript:del_divs('$Data[0]','$par')><img src='/BSOS/images/crm/icon-delete.gif' width=10 height=9 alt='' border=0 align=left></a>";

				  if($Data[7]!='None')
				    {
					if($Data[8]=='INACTIVE')
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/hisreviewmanage.php?addr=$Data[0]','compArchieve',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
					else
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/viewcompanySummary.php?addr=$Data[0]','',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
				    }
				   else
				   {
						$DispData="&nbsp;<font class='crmsummary-lnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
				   }
			}



			echo $put_var.$move."&nbsp;&nbsp;&nbsp;&nbsp;<img src='/BSOS/images/email/L.png' valign=top border=0><img src='$Icon' valign=middle border=0>".$DispData."</font><br/>";
			if($checkrows>0)
				divTree($Data[0],$move."&nbsp;&nbsp;&nbsp;&nbsp;",$curSno);

		}//while
	}



//to get the edit  divisions tree
	function div_editTree($par,$move,$curSno)
	{
		global $maildb,$db,$username;
		$Sql="select sno,address1,address2,city,state,country,zip,IF(accessto='ALL','Public',IF(accessto='$username','Private',IF(FIND_IN_SET('$username',accessto)>0,'Share','None'))),status,cname from staffoppr_cinfo where  parent='".$par."' order by cname";
		$Res=mysql_query($Sql,$db);
		$Tot_Cols=mysql_num_fields($Res);
		while($Data=mysql_fetch_row($Res))
		{
			$CheckChild_sql="select sno from staffoppr_cinfo where parent=$Data[0]";
			$CheckChild_res=mysql_query($CheckChild_sql,$db);
			$checkrows=mysql_num_rows($CheckChild_res);
			if($checkrows>0)
				$Icon='/BSOS/images/crm/icon-div.gif';
			else
			$Icon='/BSOS/images/crm/icon-branch.gif';
			$divAddr='';
			$collen=2;
			$divAddr=getCompDivisionAddress($Data[9],$Data[1],$Data[2],$Data[3],$Data[4],getCountry($Data[5]),$Data[6]);
			if($Data[0]==$curSno)
				{
					$put_var='&nbsp;&nbsp;&nbsp;&nbsp;';
					$DispData="&nbsp;<font  class='crmsummary-contentbolddata1'>".html_tls_specialchars(stripslashes($divAddr),ENT_QUOTES)."</font>";
			 	}
			 else
			  {
				   $put_var = "<a class=remind-delete-align href=javascript:del_editdivs('$Data[0]','$curSno')><img src='/BSOS/images/crm/icon-delete.gif' width=10 height=9 alt='' border=0 align=left></a>";

				  if($Data[7]!='None')
				    {
					if($Data[8]=='INACTIVE')
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/hisreviewmanage.php?addr=$Data[0]','compArchieve',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars(stripslashes($divAddr),ENT_QUOTES)."</a>";
					}
					else
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/viewcompanySummary.php?addr=$Data[0]','',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars(stripslashes($divAddr),ENT_QUOTES)."</a>";
					}
				    }
				   else
				   {
						$DispData="&nbsp;<font class='crmsummary-lnk'>".html_tls_specialchars(stripslashes($divAddr),ENT_QUOTES)."</font>";
				   }
			}



			echo $put_var.$move."<img src='/BSOS/images/email/L.png' valign=top border=0><img src='$Icon' valign=middle border=0>".$DispData."</font><br/>";
			if($checkrows>0)
				div_editTree($Data[0],$move."&nbsp;&nbsp;&nbsp;&nbsp;",$curSno);

		}//while
	}


//to get the company summary address divisions tree
	function div_addr_Tree($par,$move,$curSno,$module)
	{
		global $maildb,$db,$username;
		$Sql="select sno,address1,address2,city,state,country,zip,IF(accessto='ALL','Public',IF(accessto='$username','Private',IF(FIND_IN_SET('$username',accessto)>0,'Share','None'))),status,cname from staffoppr_cinfo where  parent='".$par."' order by cname";
		$Res=mysql_query($Sql,$db);
		$Tot_Cols=mysql_num_fields($Res);
		while($Data=mysql_fetch_row($Res))
		{
			$CheckChild_sql="select sno from staffoppr_cinfo where parent=$Data[0]";
			$CheckChild_res=mysql_query($CheckChild_sql,$db);
			$checkrows=mysql_num_rows($CheckChild_res);
			if($checkrows>0)
				$Icon='/BSOS/images/crm/icon-div.gif';
			else
			$Icon='/BSOS/images/crm/icon-branch.gif';
			$divAddr='';
			$divAddr=getCompDivisionAddress($Data[9],$Data[1],$Data[2],$Data[3],$Data[4],getCountry($Data[5]),$Data[6]);
			if($Data[0]==$curSno)
				{

					$DispData="&nbsp;<font  class='crmsummary-contentbolddata1'>".html_tls_specialchars(stripslashes($divAddr),ENT_QUOTES)."</font>";
			 	}
			 else
			  {
				  if($Data[7]!='None')
				    {
					if($Data[8]=='INACTIVE')
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/hisreviewmanage.php?addr=$Data[0]&module=$module','compArchieve',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars(stripslashes($divAddr),ENT_QUOTES)."</a>";
					}
					else
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Marketing/Companies/viewcompanySummary.php?addr=$Data[0]&module=$module','',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars(stripslashes($divAddr),ENT_QUOTES)."</a>";
					}
				    }
				   else
				   {
						$DispData="&nbsp;<font class='crmsummary-lnk'>".html_tls_specialchars(stripslashes($divAddr),ENT_QUOTES)."</font>";
				   }
			}

			echo $move."<img src='/BSOS/images/email/L.png' valign=top border=0><img src='$Icon' valign=middle border=0>".$DispData."</font><br/>";
			if($checkrows>0)
				div_addr_Tree($Data[0],$move."&nbsp;&nbsp;&nbsp;&nbsp;",$curSno,$module);

		}//while
	}


	function is_garbage_session()
	{
		$sess_save_path=ini_get("session.save_path");
		$session_garbage_time=ini_get("session.gc_maxlifetime");
		$sess_id=session_id();

		$filename="$sess_save_path/sess_$sess_id";
		if (filemtime($filename) + $session_garbage_time < time())
		{
			unlink($filename);
			return false;
		}
		else
		{
			return true;
		}
	}

	// To get Owner (Source) from users table
	//function modified by harika on 13/02/2008 added stripslashes in return statement
	function getOwnerName($uID)
	{
		global $maildb,$db;

		$owner_Query = "SELECT name FROM users WHERE username = '".$uID."'";
		$owner_Result = mysql_query($owner_Query,$db);
		$owner_Row = mysql_fetch_row($owner_Result);

		return stripslashes($owner_Row[0]);
	}

	// compare two values are equal and return "selected" - used in email module
	function compose_sel($a,$b)
	{
		if($a==$b)
			return "selected";
		else
			return "";
	}

	// compare two values are equal and return "checked" - used in email module
	function sent_check($a,$b)
	{
		if($a==$b)
			return "checked";
		else
			return "";
	}

	// compare two values are equal and return first character as capital - used in email module
	function compose_ucsel($a,$b)
	{
		if($a==$b)
			return ucfirst($a);
		else
			return "";
	}

	// compare two values are equal and return "selected"
	function getSel($a,$b)
	{
		if (strtolower($a) == strtolower($b))
			return "selected";
		else
			return "";
	}

	// compare two values are equal and return "checked"
	function getChk($a,$b)
	{
		if ($a == $b)
			return "checked";
		else
			return "";
	}

	// compare two values are equal and return first character as capital
	function getUC($a,$b)
	{
		if ($a == $b)
			return ucfirst($a);
		else
			return "";
	}

	// to clear cookies
	function clearCookies($obj,$parent)
	{
		while(list($key,$value) = each($obj))
		{
			if(!strstr($key,'CRM_') && !strstr($key,'ACC_') && !strstr($key,'HRM_') && !strstr($key,'Accounting_') && !strstr($key,'ADMIN_') && !strstr($key,'Collaboration_') && !strstr($key,'SelfService_'))
			{
				if(is_array($value))
				{
					clearCookies($value,$key);
				}
				else
				{
					$cval=$parent=="" ? $key : $parent."[".$key."]";
					ac_fs_setcookie($cval,"",0,"/"); 
				}
			}
		}
	}

	function WrapText($text,$line_length=50,$line_break="")
	{
		if(strlen($line_break)==0)
			$line_break="\n";
		$lines=explode("\n",str_replace("\r","\n",str_replace("\r\n","\n",$text)));
		for($wrapped="",$line=0;$line<count($lines);$line++)
		{
			for($text_line=$lines[$line];strlen($text_line)>$line_length;)
			{
				if(GetType($cut=strrpos(substr($text_line,0,$line_length)," "))!="integer")
				{
					$wrapped.=substr($text_line,0,$line_length).$line_break;
					$cut=$line_length;
				}
				else
				{
					$wrapped.=substr($text_line,0,$cut).$line_break;
					$cut++;
				}
				$text_line=substr($text_line,$cut);
			}
			$wrapped.=$text_line.$line_break;
		}
		return($wrapped);
	}

	function ContText($text,$line_length)
	{
		if(strlen($text)>=$line_length)
			$continue=substr($text,0,$line_length)."...";
		else
			$continue=$text;
		return($continue);
	}

	// Delete files from folder
	function delete_files($username)
	{
		// Commented the code due to attachments being blank in when drafts / inprogress attached emails processed after calender invite is sent.
		// global $WDOCUMENT_ROOT,$attach_folder;

		// $userdir=$WDOCUMENT_ROOT;
		// if($attach_folder!="")
		// {
		// 	$userdir.="/".$attach_folder;
		// 	if(file_exists($userdir))
		// 	{
		// 		$d=dir($userdir);
		// 		while($entry=$d->read())
		// 		{
		// 			if( ($entry!=".") and ($entry!="..") )
		// 			{
		// 				 unlink($userdir."/".$entry);
		// 			}
		// 		}
		// 		rmdir($userdir);
		// 	}
		// }
		// else
		// {
		// 	if(file_exists($userdir))
		// 	{
		// 		$d=dir($userdir);
		// 		while($entry=$d->read())
		// 		{
		// 			if( ($entry!=".") and ($entry!="..") )
		// 			{
		// 				if(is_dir($userdir."/".$entry))
		// 					delFolder($userdir."/".$entry);
		// 				else
		// 					unlink($userdir."/".$entry);
		// 			}
		// 		}
		// 		$d->close();
		// 	}
		// }

		return TRUE;
	}

	// Delete folder
	function delFolder($par_fol)
	{
		if(is_dir($par_fol))
		{
			$d=dir($par_fol);
			while($entry=$d->read())
			{
				if( ($entry!=".") and ($entry!="..") )
				{
					if(is_dir($par_fol."/".$entry))
						delFolder($par_fol."/".$entry);
					else
						unlink($par_fol."/".$entry);
				}
			}
			rmdir($par_fol);
		}
	}

	function prepareCID(&$matter,&$cid_body,$mesid)
	{
		global $maildb,$db,$contactmail,$fromOutbox,$boun_related_part;

		if($contactmail=="yes")
			$que="select body from contact_email_body where id='".$mesid."'";
		else
			$que="select body from mail_headers_body where id='".$mesid."'";

		$res=mysql_query($que,$maildb);
		$row=mysql_fetch_row($res);
		$realmes=$row[0];

		if(preg_match_all("/educeit-cid:(.[^\">]*)/", $realmes, $matches))
		{
			foreach($matches[1] as $cid)
			{
				$filename=$cid;
				if($contactmail=="yes")
					$find_url="/BSOS/getcid.php?mesid=$mesid&amp;lookfor=contact_em_attach&amp;filename=$filename";
				else
					$find_url="/BSOS/getcid.php?mesid=$mesid&amp;lookfor=mail_attachs&amp;filename=$filename";

				if(strpos($matter,$find_url))
				{
					if($contactmail=="yes")
						$que="select body from contact_em_attach where contactemailsno=$mesid and attachname='".$filename."'";
					else
						$que="select filecontent from mail_attachs where mailid=$mesid and filename='".$filename."'";

					$res=mysql_query($que,$maildb);
					$row=mysql_fetch_row($res);

					$cidcontent=chunk_split(base64_encode($row[0]));
					$temp_cid = md5(uniqid("$filename"));
					$matter = str_replace($find_url,"cid:$temp_cid",$matter);

					$strLIT = strtolower($filename);
					$strFileType = "jpg";
					if(strpos($strLIT,"jpg") || strpos($strLIT,"jpeg"))
						$strFileType="jpg";
					else if(strpos($strLIT,"png"))
						$strFileType="png";
					else if(strpos($strLIT,"gif"))
						$strFileType="gif";

					$cid_body.="--Message-Boundary$boun_related_part\n";
					$cid_body.="Content-Type: image/$strFileType; name=\"$filename\"\n";
					$cid_body.="Content-Transfer-Encoding: base64\n";
					$cid_body.="Content-ID: <$temp_cid>\n\n";
					$cid_body.=$cidcontent."\n\n";
				}
			}
		}

		if($fromOutbox=="YES")
		{
			$inlineque="select filename from mail_attachs where mailid=$mesid and inline='true'";
			$inlineres=mysql_query($inlineque,$maildb);
			while($inliners=mysql_fetch_array($inlineres))
			{
				$find_url="/BSOS/getcid.php?mesid=$mesid&amp;lookfor=mail_attachs&amp;filename=".$inliners[0]."";
				if(strpos($matter,$find_url))
				{
					$que="select filecontent from mail_attachs where mailid=$mesid and filename='".$inliners[0]."'";
					$res=mysql_query($que,$maildb);
					$row=mysql_fetch_row($res);

					$cidcontent=chunk_split(base64_encode($row[0]));
					$temp_cid = md5(uniqid("$filename"));
					$matter = str_replace($find_url,"cid:$temp_cid",$matter);

					$strLIT = strtolower($filename);
					$strFileType = "jpg";
					if(strpos($strLIT,"jpg") || strpos($strLIT,"jpeg"))
						$strFileType="jpg";
					else if(strpos($strLIT,"png"))
						$strFileType="png";
					else if(strpos($strLIT,"gif"))
						$strFileType="gif";

					$cid_body.="--Message-Boundary$boun_related_part\n";
					$cid_body.="Content-Type: image/$strFileType; name=\"$filename\"\n";
					$cid_body.="Content-Transfer-Encoding: base64\n";
					$cid_body.="Content-ID: <$temp_cid>\n\n";
					$cid_body.=$cidcontent."\n\n";
				}
			}
		}
	}

	function prepareBody($matter,&$mailheaders,$mailtype)
	{
		global $maildb,$db,$companyuser,$msgs,$contactmail,$docs,$sesstr,$boun_mixed_part,$boun_related_part,$boun_alternative_part,$inlinemailid,$docpanelselect,$docmangselect,$candidocs,$candiresume,$jorderdocs,$companydocs,$candiprofile,$jorderprofile,$companyprofile,$CharSet_mail;

		$CharSet_mail=AssignEmailCharset($CharSet_mail);
		$mailtype=$mailtype=="" ? "text/html" : $mailtype;
		$related_part_logo=(strpos($matter,"logo.php?id=$companyuser") ? "logo" : (strpos($matter,"/BSOS/logo.php?uploadImageStat=true&amp;imgid=") ? "upload" : ""));
		$related_part_cid=strpos($matter,"getcid.php") ? "cid" : "";
		$matter=str_replace("<!--mail-->","",$matter);

		if($related_part_logo!="" || $related_part_cid!="")
		{
			if($related_part_cid!="")
				prepareCID($matter,$cid_body,$msgs);

			if($related_part_logo!="")
			{
				$lque="select image_data,image_type,image_size from company_logo";
				$lres=mysql_query($lque,$db);
				$lrow=mysql_fetch_row($lres);
				$logocontent=chunk_split(base64_encode($lrow[0]));

				$cid = md5(uniqid("$companyuser"));
				$matter = str_replace("/BSOS/logo.php?id=$companyuser","cid:$cid",$matter);

				$strLIT = strtolower($lrow[1]);
				$strFileType = ".jpg";
				if(strpos($strLIT,"jpg") || strpos($strLIT,"jpeg"))
					$strFileType=".jpg";
				else if(strpos($strLIT,"gif"))
					$strFileType=".gif";

				$logo_body.="--Message-Boundary$boun_related_part\n";
				$logo_body.="Content-Type: $lrow[1]; name=\"".$companyuser.$strFileType."\"\n";
				$logo_body.="Content-Transfer-Encoding: base64\n";
				$logo_body.="Content-ID: <$cid>\n\n";
				$logo_body.=$logocontent."\n\n";
			}

			if(strpos("*".$matter,"/BSOS/logo.php?uploadImageStat=true&amp;imgid=")>0) //This loop executes when user uploads any header image.
			{

				$posStr =  strpos($matter,"/BSOS/logo.php?uploadImageStat=true&amp;imgid=");
				$newStr = substr($matter,$posStr+46);
				$posNew =  strpos($newStr,"\"");
				$imgSno = (int)substr($newStr,0,$posNew);

				$upque="select img_content,img_type,img_size,name,img_name from header_footer where sno='".$imgSno."'";
				$upres=mysql_query($upque,$db);
				if(mysql_num_rows($upres)>0)
				{
					$uprow=mysql_fetch_row($upres);
					$upLogoContent=chunk_split(base64_encode($uprow[0]));
					$HImgName="";

					$Hdcid = md5(uniqid("$imgSno"));
					$matter = str_replace("/BSOS/logo.php?uploadImageStat=true&amp;imgid=$imgSno","cid:$Hdcid",$matter);

					$upstrLIT = strtolower($uprow[1]);
					if(trim($uprow[4]!=''))
					{
						$HImgName=$uprow[4];
					}
					else
					{
						$HImgName="Image".$imgSno;
						$upFileType = ".jpg";
						if(strpos($upstrLIT,"jpg") || strpos($upstrLIT,"jpeg"))
							$upFileType=".jpg";
						else if(strpos($upstrLIT,"gif"))
							$upFileType=".gif";

						$HImgName.=$upFileType;
					}
					$logo_body.="--Message-Boundary$boun_related_part\n";
					$logo_body.= "Content-Type: $uprow[1]; name=\"".$HImgName."\"\n";
					$logo_body.= "Content-Transfer-Encoding: base64\n";
					$logo_body.= "Content-ID: <$Hdcid>\n\n";
					$logo_body.= $upLogoContent."\n\n";
				}
			}
		}

		if($docs!="" || $sesstr!="" || ((int)$inlinemailid>0) || $docpanelselect!="" || $docmangselect!="" || $candidocs!="" || $candiresume!="" || $jorderdocs!="" || $companydocs!="" || $candiprofile!="" || $jorderprofile!="" || $companyprofile!="")
		{
			array_push($mailheaders,"Content-Type: multipart/mixed; boundary=\"Message-Boundary$boun_mixed_part\"","Content-Transfer-Encoding: 8bit");
			$ret_body.="--Message-Boundary$boun_mixed_part\n";
		}
		else if($related_part_logo!="" || $related_part_cid!="")
		{
			array_push($mailheaders,"Content-Type: multipart/related; boundary=\"Message-Boundary$boun_related_part\"");
		}
		else if($mailtype=="text/html")
		{
			array_push($mailheaders,"Content-Type: multipart/alternative; boundary=\"Message-Boundary$boun_alternative_part\"");
		}
		else
		{
			array_push($mailheaders,"Content-Type: text/plain; Charset=\"$CharSet_mail\"");
			array_push($mailheaders,"Content-Transfer-encoding: 7bit");
		}

		if(($docs!="" || $sesstr!="" || ((int)$inlinemailid>0) || $docpanelselect!="" || $docmangselect!="" || $candidocs!="" ||$candiresume!="" || $jorderdocs!="" || $companydocs!="" || $candiprofile!=""||$jorderprofile!="" || $companyprofile!="") && ($related_part_logo!="" || $related_part_cid!=""))
			$ret_body.="Content-Type: multipart/related; boundary=\"Message-Boundary$boun_related_part\"\n\n";

		if($related_part_logo!="" || $related_part_cid!="")
			$ret_body.="--Message-Boundary$boun_related_part\n";

		if($mailtype=="text/html" && ($related_part_logo!="" || $related_part_cid!="" || ((int)$inlinemailid>0) || $docs!="" || $sesstr!="" || $docpanelselect!="" || $docmangselect!="" || $candidocs!="" || $candiresume!="" || $jorderdocs!="" || $companydocs!="" || $candiprofile!=""||$jorderprofile!=""||$companyprofile!=""))
			$ret_body.="Content-type: multipart/alternative; boundary=\"Message-Boundary$boun_alternative_part\"\n\n";

		if($mailtype=="text/html")
			$ret_body.="--Message-Boundary$boun_alternative_part\n";

		if($mailtype=="text/html" || $related_part_logo!="" || $related_part_cid!="" || $docs!="" || $sesstr!=""||$docpanelselect!="" || $docmangselect!="" || $candidocs!="" || $candiresume!="" || $jorderdocs!="" || $companydocs!="" || $candiprofile!=""||$jorderprofile!=""||$companyprofile!="")
		{
			$ret_body.="Content-Type: text/plain;\n";
			$ret_body.=" Charset=\"".$CharSet_mail."\";\n";
			$ret_body.="Content-Transfer-encoding: 7bit\n\n";

			if($mailtype=="text/plain")
				$ret_body.=strip_tags($matter)."\n\n";
			else
				$ret_body.=html2text($matter)."\n\n";
		}
		else
		{
			$ret_body=strip_tags($matter)."\n\n";
		}

		if($mailtype=="text/html")
		{
			$ret_body.="--Message-Boundary$boun_alternative_part\n";
			$ret_body.="Content-type: text/html;\n";
			$ret_body.=" charset=\"".$CharSet_mail."\";\n";
			$ret_body.="Content-Transfer-encoding: 7bit\n\n";
			$ret_body.=wordwrap($matter,750)."\n\n";
			$ret_body.="--Message-Boundary$boun_alternative_part--\n";
		}

		if($related_part_logo!="" || $related_part_cid!="")
		{
			$ret_body.=$cid_body.$logo_body;
			$ret_body.="--Message-Boundary$boun_related_part--\n";
		}

		return $ret_body;
	}

	function prepareAttachsNEW(&$file_name,&$file_size,&$file_type,&$file_con,&$attach_body)
	{
		global $WDOCUMENT_ROOT,$filename,$filetype,$filename1,$filetype1,$filename2,$filetype2,$docs,$maildb,$db,$hfAttach,$sesstr, $attach_folder,$docType,$boun_mixed_part,$docpanelselect,$docmangselect,$candidocs,$candiresume,$jorderdocs,$companydocs, $candiprofile,$jorderprofile,$companyprofile;

		require_once("class.MimeType.inc");
		if(!$mime)
			$mime = new MimeType();

		require("candidatehtmldocs.php");
		require("joborderdochtml.php");
		require("class.profiledochtml.inc");

		$Obj_Profile = new ManageProfileAttachments($db,$username);
		$flag=0;
		if(trim($hfAttach)!="")
		{
			$nos=explode('|^', stripslashes($sesstr));
			for($i=0;$i<count($nos);$i++)
			{
				$varfname=explode('|-', stripslashes($nos[$i]));
                $attfilename=$WDOCUMENT_ROOT."/".$attach_folder."/".$varfname[0];
				$file = fopen($attfilename, "r");

				$filetype=$mime->getMimeType($varfname[1]);
				$filetype=($filetype == "") ? mime_content_type($attfilename) : $filetype;

				$contents = fread($file, filesize($attfilename));
				fclose($file);
				$encoded_attach = chunk_split(base64_encode($contents));

				$attach_body.="--Message-Boundary$boun_mixed_part\n";
				$attach_body.="Content-Type: ".$filetype."; name=\"$varfname[1]\"\n";
				$attach_body.="Content-Transfer-Encoding: base64\n";
				$attach_body.="Content-disposition: attachment; filename=\"$varfname[1]\"\n\n";
				$attach_body.="$encoded_attach\n";

				$file_name[$flag]=$varfname[1];
				$file_size[$flag]=filesize($attfilename);
				$file_type[$flag]=$filetype;
				$file_con[$flag]=$contents;
				$flag++;
			}
		}

		if($docs!="")
		{
			$sdoc=explode("|",$docs);
			for($i=0;$i<count($sdoc);$i++)
			{
                if($docType=="Candiadte")
                    $que="select body,docname from contact_doc where sno=".$sdoc[$i];
                else
    				$que="select filecontent,filename,filetype,size from webfolder where sno=".$sdoc[$i];

				$res=mysql_query($que,$db);
				$row=mysql_fetch_row($res);
				$contents =$row[0] ;

				$filetype=$row[2];
				$filename=$row[1];
				$encoded_attach = chunk_split(base64_encode($contents));
				$attach_body.="--Message-Boundary$boun_mixed_part\n";
				$attach_body.="Content-Type: ".$filetype."; name=\"$filename\"\n";
				$attach_body.="Content-Transfer-Encoding: base64\n";
				$attach_body.="Content-disposition: attachment; filename=\"$filename\"\n\n";
				$attach_body.="$encoded_attach\n";
				$file_name[$flag]=$filename;
				$file_size[$flag]=$row[3];
				$file_type[$flag]=$filetype;
				$file_con[$flag]=$contents;
				$flag++;
			}
		}

		if($docpanelselect != "")
		{
		  $docpanl=explode("|^",$docpanelselect);
		  for($i=0;$i<count($docpanl);$i++)
		  {
		    $exp_cpanel = explode("|-",$docpanl[$i]);

			$exp_prf=explode("cand",$exp_cpanel[0]);
			$exp_joprof=explode("req",$exp_cpanel[0]);
			$exp_res = explode("res",$exp_cpanel[0]);
			if(count($exp_prf)>1)
			{
			  $contents=CandidateHtmlDoc($exp_prf[1]);
			  $fname=$exp_cpanel[1];
			  $filetype="html";
			}
			else if(count($exp_joprof)>1)
			{
			   $job_id=$exp_joprof[1];
			   $contents=Joborderhtmldoc($job_id);
			   $fname=$exp_cpanel[1];
			   $filetype="html";
			}
			else if(count($exp_res)>1)
			{
			  $que="select sno,res_name,markadd,filecontent,filetype from con_resumes where sno='".$exp_res[1]."'";
			  $res=mysql_query($que,$db);
			  $row=mysql_fetch_row($res);
			  $contents =$row[3];
			  if($row[2]=="")
			  $fname=$row[1].".doc";
			  else
			  $fname=$row[2];
			  $filetype=$row[4];
			}
			else
			{
				$que="select doctype,docname,body from contact_doc where sno='".$exp_cpanel[0]."'";
				$res=mysql_query($que,$db);
				$row=mysql_fetch_array($res);
				$contents =$row['body'];
				$fname=$row['docname'];
				$filetype=$row['doctype'];
			}

			$encoded_attach = chunk_split(base64_encode($contents));

			$attach_body.="--Message-Boundary$boun_mixed_part\n";
			$attach_body.="Content-Type: ".$filetype."; name=\"$fname\"\n";
			$attach_body.="Content-Transfer-Encoding: base64\n";
			$attach_body.="Content-disposition: attachment; filename=\"$fname\"\n\n";
			$attach_body.="$encoded_attach\n";

			$file_name[$flag]=$fname;
			$file_type[$flag]=$filetype;
			$file_con[$flag]=$contents;
			$flag++;
		  }
		}

		if($docmangselect != "")
		{
		  $docmng=explode("|^",$docmangselect);
		  for($i=0;$i<count($docmng);$i++)
		  {
		    $exp_docmng = explode("|-",$docmng[$i]);
			$que="select filecontent,filename,filetype,size from webfolder where sno='".$exp_docmng[0]."'";
       	    $res=mysql_query($que,$db);
		    $row=mysql_fetch_row($res);
		    $contents =$row[0] ;

			$filetype=$row[2];
			$filename=$row[1];
			$encoded_attach = chunk_split(base64_encode($contents));
			$attach_body.="--Message-Boundary$boun_mixed_part\n";
			$attach_body.="Content-Type: ".$filetype."; name=\"$filename\"\n";
			$attach_body.="Content-Transfer-Encoding: base64\n";
			$attach_body.="Content-disposition: attachment; filename=\"$filename\"\n\n";
			$attach_body.="$encoded_attach\n";
			$file_name[$flag]=$filename;
			$file_size[$flag]=$row[3];
			$file_type[$flag]=$filetype;
			$file_con[$flag]=$contents;
			$flag++;
		  }
		}

		if($candidocs != "")
		{
		  $docand=explode("|canddocs^",$candidocs);
		  for($i=0;$i<count($docand);$i++)
		  {
		    $exp_docand = explode("|-",$docand[$i]);
			$que="select doctype,docname,body from contact_doc where sno='".$exp_docand[0]."'";
       	    $res=mysql_query($que,$db);
    	    $row=mysql_fetch_array($res);
    	    $contents =$row['body'];
		    $fname=$row['docname'];
		    $filetype=$row['doctype'];

			$encoded_attach = chunk_split(base64_encode($contents));

			$attach_body.="--Message-Boundary$boun_mixed_part\n";
			$attach_body.="Content-Type: ".$filetype."; name=\"$fname\"\n";
			$attach_body.="Content-Transfer-Encoding: base64\n";
			$attach_body.="Content-disposition: attachment; filename=\"$fname\"\n\n";
			$attach_body.="$encoded_attach\n";

			$file_name[$flag]=$fname;
			$file_type[$flag]=$filetype;
			$file_con[$flag]=$contents;
			$flag++;
		  }
		}

		if($candiresume!= "")
		{
		  $dores=explode("|candresume^",$candiresume);
		  for($i=0;$i<count($dores);$i++)
		  {
			  $exp_dores = explode("|-",$dores[$i]);
			  $que="select sno,res_name,markadd,filecontent,filetype from con_resumes where sno='".$exp_dores[0]."'";
			  $res=mysql_query($que,$db);
			  $row=mysql_fetch_row($res);
			  $contents =$row[3];
			  if($row[2]=="")
			  $fname=$row[1].".doc";
			  else
			  $fname=$row[2];
			  $filetype=$row[4];
			  $encoded_attach = chunk_split(base64_encode($contents));
			  $attach_body.="--Message-Boundary$boun_mixed_part\n";
			  $attach_body.="Content-Type: ".$filetype."; name=\"$fname\"\n";
			  $attach_body.="Content-Transfer-Encoding: base64\n";
			  $attach_body.="Content-disposition: attachment; filename=\"$fname\"\n\n";
			  $attach_body.="$encoded_attach\n";
			  $file_name[$flag]=$fname;
			  $file_type[$flag]=$filetype;
			  $file_con[$flag]=$contents;
			  $flag++;
		   }
		}
		if($jorderdocs != "")
		{
		  $docjo=explode("|jorderdocs^",$jorderdocs);
		  for($i=0;$i<count($docjo);$i++)
		  {
		    $exp_docjo = explode("|-",$docjo[$i]);
			$que="select doctype,docname,body from contact_doc where sno='".$exp_docjo[0]."'";
       	    $res=mysql_query($que,$db);
    	    $row=mysql_fetch_array($res);
    	    $contents =$row['body'];
		    $fname=$row['docname'];
		    $filetype=$row['doctype'];

			$encoded_attach = chunk_split(base64_encode($contents));

			$attach_body.="--Message-Boundary$boun_mixed_part\n";
			$attach_body.="Content-Type: ".$filetype."; name=\"$fname\"\n";
			$attach_body.="Content-Transfer-Encoding: base64\n";
			$attach_body.="Content-disposition: attachment; filename=\"$fname\"\n\n";
			$attach_body.="$encoded_attach\n";

			$file_name[$flag]=$fname;
			$file_type[$flag]=$filetype;
			$file_con[$flag]=$contents;
			$flag++;
		  }
		}

		if($companydocs != "")
		{
		  $doccomp=explode("|compdocs^",$companydocs);
		  for($i=0;$i<count($doccomp);$i++)
		  {
		    $exp_doccomp = explode("|-",$doccomp[$i]);
			$que="SELECT doctype,docname,body FROM contact_doc WHERE sno='".$exp_doccomp[0]."'";
       	    $res=mysql_query($que,$db);
    	    $row=mysql_fetch_array($res);
    	    $contents =$row['body'];
		    $fname=$row['docname'];
		    $filetype=$row['doctype'];

			$encoded_attach = chunk_split(base64_encode($contents));

			$attach_body.="--Message-Boundary$boun_mixed_part\n";
			$attach_body.="Content-Type: ".$filetype."; name=\"$fname\"\n";
			$attach_body.="Content-Transfer-Encoding: base64\n";
			$attach_body.="Content-disposition: attachment; filename=\"$fname\"\n\n";
			$attach_body.="$encoded_attach\n";

			$file_name[$flag]=$fname;
			$file_type[$flag]=$filetype;
			$file_con[$flag]=$contents;
			$flag++;
		  }
		}

		if($candiprofile!= "")
		{
		  $candprf=explode("|candprofile^",$candiprofile);
		  for($i=0;$i<count($candprf);$i++)
		  {
			  $exp_candprf = explode("|-",$candprf[$i]);
			  $contents=CandidateHtmlDoc($exp_candprf[0]);
			  $fname=$exp_candprf[1];
			  $filetype="html";
			  $encoded_attach = chunk_split(base64_encode($contents));
			  $attach_body.="--Message-Boundary$boun_mixed_part\n";
			  $attach_body.="Content-Type: ".$filetype."; name=\"$fname\"\n";
			  $attach_body.="Content-Transfer-Encoding: base64\n";
			  $attach_body.="Content-disposition: attachment; filename=\"$fname\"\n\n";
			  $attach_body.="$encoded_attach\n";
			  $file_name[$flag]=$fname;
			  $file_type[$flag]=$filetype;
			  $file_con[$flag]=$contents;
			  $flag++;
		  }
		}
		if($jorderprofile !="")
		{
		  $joprf=explode("|joborderprofile^",$jorderprofile);
		  for($i=0;$i<count($joprf);$i++)
		  {
			   $exp_joprof = explode("|-",$joprf[$i]);
			   $job_id=$exp_joprof[0];
			   $contents=Joborderhtmldoc($job_id);
			   $fname=$exp_joprof[1];
			   $filetype="html";
			   $encoded_attach = chunk_split(base64_encode($contents));
			   $attach_body.="--Message-Boundary$boun_mixed_part\n";
			   $attach_body.="Content-Type: ".$filetype."; name=\"$fname\"\n";
			   $attach_body.="Content-Transfer-Encoding: base64\n";
			   $attach_body.="Content-disposition: attachment; filename=\"$fname\"\n\n";
			   $attach_body.="$encoded_attach\n";
			   $file_name[$flag]=$fname;
			   $file_type[$flag]=$filetype;
			   $file_con[$flag]=$contents;
			   $flag++;
		   }
		 }
		 if($companyprofile !="")
		 {
		  $coprf=explode("|compprofile^",$companyprofile);
		  for($i=0;$i<count($coprf);$i++)
		  {
			   $exp_coprof = explode("|-",$coprf[$i]);
			   $comp_id=$exp_coprof[0];
			   $contents=$Obj_Profile->CompanyProfile($comp_id);
			   $fname=$exp_coprof[1];
			   $filetype="html";
			   $encoded_attach = chunk_split(base64_encode($contents));
			   $attach_body.="--Message-Boundary$boun_mixed_part\n";
			   $attach_body.="Content-Type: ".$filetype."; name=\"$fname\"\n";
			   $attach_body.="Content-Transfer-Encoding: base64\n";
			   $attach_body.="Content-disposition: attachment; filename=\"$fname\"\n\n";
			   $attach_body.="$encoded_attach\n";
			   $file_name[$flag]=$fname;
			   $file_type[$flag]=$filetype;
			   $file_con[$flag]=$contents;
			   $flag++;
		   }
		 }

		if($flag>0)
			$attach_body.="--Message-Boundary$boun_mixed_part--\n";
		return $flag;
	}

	function prepareAttachs(&$file_name,&$file_size,&$file_type,&$file_con,&$attach_body)
	{
		global $WDOCUMENT_ROOT,$filename,$filetype,$filename1,$filetype1,$filename2,$filetype2,$filename3,$filetype3,$docs,$maildb,$db,$boun_mixed_part;

		$flag=0;
		if($filename!="" || $filename1!="" || $filename2!="" || $filename3!="")
		{
			if ($filename!="")
			{
				$filename=stripslashes($filename);
				$attfilename=$WDOCUMENT_ROOT."/".$filename;
				$file = fopen($attfilename, "r");
				$contents = fread($file, filesize($attfilename));
				fclose($file);
				$encoded_attach = chunk_split(base64_encode($contents));

				$attach_body .= "--Message-Boundary$boun_mixed_part\n";
				$attach_body .= "Content-Type: ".$filetype."; name=\"$filename\"\n";
				$attach_body .= "Content-Transfer-Encoding: base64\n";
				$attach_body .= "Content-disposition: attachment; filename=\"$filename\"\n\n";
				$attach_body .= "$encoded_attach\n";

				$file_name[$flag]=$filename;
				$file_size[$flag]=filesize($attfilename);
				$file_type[$flag]=$filetype;
				$file_con[$flag]=$contents;
				$flag++;
			}
			if($filename1!="")
			{
				$filename1=stripslashes($filename1);
				$attfilename=$WDOCUMENT_ROOT."/".$filename1;
				$file = fopen($attfilename, "r");
				$contents = fread($file, filesize($attfilename));
				fclose($file);
				$encoded_attach = chunk_split(base64_encode($contents));

				$attach_body .= "--Message-Boundary$boun_mixed_part\n";
				$attach_body .= "Content-Type: ".$filetype1."; name=\"$filename1\"\n";
				$attach_body .= "Content-Transfer-Encoding: base64\n";
				$attach_body .= "Content-disposition: attachment; filename=\"$filename1\"\n\n";
				$attach_body .= "$encoded_attach\n";

				$file_name[$flag]=$filename1;
				$file_size[$flag]=filesize($attfilename);
				$file_type[$flag]=$filetype1;
				$file_con[$flag]=$contents;
				$flag++;
			}
			if($filename2!="")
			{
					$filename2=stripslashes($filename2);
				$attfilename=$WDOCUMENT_ROOT."/".$filename2;
				$file = fopen($attfilename, "r");
				$contents = fread($file, filesize($attfilename));
				fclose($file);
				$encoded_attach = chunk_split(base64_encode($contents));

				$attach_body .= "--Message-Boundary$boun_mixed_part\n";
				$attach_body .= "Content-Type: ".$filetype2."; name=\"$filename2\"\n";
				$attach_body .= "Content-Transfer-Encoding: base64\n";
				$attach_body .= "Content-disposition: attachment; filename=\"$filename2\"\n\n";
				$attach_body .= "$encoded_attach\n";

				$file_name[$flag]=$filename2;
				$file_size[$flag]=filesize($attfilename);
				$file_type[$flag]=$filetype2;
				$file_con[$flag]=$contents;
				$flag++;
			}
			if($filename3!="")
			{
				$filename3=stripslashes($filename3);
				$attfilename=$WDOCUMENT_ROOT."/".$filename3;
				$file = fopen($attfilename, "r");
				$contents = fread($file, filesize($attfilename));
				fclose($file);
				$encoded_attach = chunk_split(base64_encode($contents));

				$attach_body .= "--Message-Boundary$boun_mixed_part\n";
				$attach_body .= "Content-Type: ".$filetype3."; name=\"$filename3\"\n";
				$attach_body .= "Content-Transfer-Encoding: base64\n";
				$attach_body .= "Content-disposition: attachment; filename=\"$filename3\"\n\n";
				$attach_body .= "$encoded_attach\n";

				$file_name[$flag]=$filename3;
				$file_size[$flag]=filesize($attfilename);

				$file_type[$flag]=$filetype3;
				$file_con[$flag]=$contents;
				$flag++;
			}

		}

		if($docs!="")
		{
			$sdoc=explode("|",$docs);
			for($i=0;$i<count($sdoc);$i++)
			{
				$que="select filecontent,filename,filetype,size from webfolder where sno=".$sdoc[$i];
				$res=mysql_query($que,$db);
				$row=mysql_fetch_row($res);
				$contents =$row[0] ;

				$filetype=$row[2];
				$filename=$row[1];
				$encoded_attach = chunk_split(base64_encode($contents));
				$attach_body .= "--Message-Boundary$boun_mixed_part\n";
				$attach_body .= "Content-Type: ".$filetype."; name=\"$filename\"\n";
				$attach_body .= "Content-Transfer-Encoding: base64\n";
				$attach_body .= "Content-disposition: attachment; filename=\"$filename\"\n\n";
				$attach_body .= "$encoded_attach\n";
				$file_name[$flag]=$filename;
				$file_size[$flag]=$row[3];
				$file_type[$flag]=$filetype;
				$file_con[$flag]=$contents;
				$flag++;
			}
		}
		if($flag>0)
			$attach_body.="--Message-Boundary$boun_mixed_part--\n";
		return $flag;
	}

	// function to parse double quotes and split Email ids like '"Ramesh , Vempati" <rameshcv@educeit.com>,mythili@akkentech.com'

	function quotesplit($line,$split=",",$temp="^")
	{
		$text=explode("\"",$line);
		for($i=0;$i<count($text);$i++)
		{
			if($j==1)
			{
				$text[$i]=str_replace($split,$temp,$text[$i]);
				$j=0;
			}
			else
			{
				$j++;
			}
			$string.=$text[$i];
			if($i<(count($text)-1))
				$string.="\"";
		}

		$cont=explode(",",$string);
		for($i=0;$i<count($cont);$i++)
		{
			$cont[$i]=str_replace($temp,$split,$cont[$i]);
		}
		return $cont;
	}

	function _do_Clear_Session()
	{
		session_unregister("page1");
		session_unregister("page111");
		session_unregister("page2");
		session_unregister("page3");
		session_unregister("page4");
		session_unregister("page5");
		session_unregister("page6");
		session_unregister("page7");
		session_unregister("page8");
		session_unregister("page9");
		session_unregister("page10");
		session_unregister("page11");
		session_unregister("page12");
		session_unregister("page13");
		session_unregister("page14");
		session_unregister("page15");
		session_unregister("page16");
		session_unregister("mes");
		session_unregister("cliusername");
		session_unregister("jobtype");
		session_unregister("posid");
		session_unregister("workenv");
		session_unregister("clientname");
		session_unregister("clientsno");
		session_unregister("position");
		session_unregister("reqskills");
		session_unregister("orderd");
		session_unregister("orderd1");
		session_unregister("jobcategory");
		session_unregister("joblevel");
		session_unregister("table1");
		session_unregister("table2");
		session_unregister("con");
		session_unregister("sub");
		session_unregister("conprof");
		session_unregister("emp");
		session_unregister("empprof");
		session_unregister("table1");
		session_unregister("table2");
		session_unregister("stafftype");
		session_unregister("orderid");
		session_unregister("folder");
		session_unregister("res_name");
		session_unregister("resname");
		session_unregister("real_name");
		session_unregister("conusername");
		session_unregister("suppage1");
		session_unregister("suppage2");
		session_unregister("suppage3");
		session_unregister("manusername");
		unset($clientsno);
	}

	function unsessionCand()
	{
		session_unregister("type");
		session_unregister("page1");
		session_unregister("page2");
		session_unregister("page4");
		session_unregister("page5");
		session_unregister("page6");
		session_unregister("page7");
		session_unregister("page8");
		session_unregister("page9");
		session_unregister("page10");
		session_unregister("page111");
		session_unregister("page12");
		session_unregister("page13");
		session_unregister("page17");
		session_unregister("res_name");
		session_unregister("resname");
		session_unregister("real_name");
		session_unregister("conusername");
		session_unregister("relocate");
		session_unregister("viewdt");
		session_unregister("candno");
		session_unregister("vdata");
		session_unregister("xmlbody");
		session_unregister("doccount");
		session_unregister("prtot");
		session_unregister("emp");
		session_unregister("con");
		session_unregister("empprof");
		session_unregister("sub");
	}

	// Function doctype(var) used to return type of the file. ex : Test.doc returns Microsoft Word Document

	function doctype($fileext)
	{
		$fileext=strtolower($fileext);

		$ext = array(
		"doc"=>"Microsoft Word Document",
		"log"=>"Log File",
		"msg"=>"Mail Message",
		"rtf"=>"Rich Text Format",
		"txt"=>"Text File",
		"wpd"=>"WordPerfect Document",
		"wps"=>"Microsoft Works Word Processor Document",
		"0123"=>"Lotus 1-2-3 Spreadsheet",
		"csv"=>"Comma Separated Values File",
		"dat"=>"Data File",
		"db"=>"Database File",
		"dll"=>"Dynamic Link Library",
		"mdb"=>"Microsoft Access Database",
		"pps"=>"PowerPoint Slide Show",
		"ppt"=>"PowerPoint Presentation",
		"sql"=>"Structured Query Language Data",
		"wks"=>"Microsoft Works Spreadsheet",
		"xls"=>"Microsoft Excel Spreadsheet",
		"xml"=>"XML File",
		"mng"=>"Multiple Network Graphic",
		"pct"=>"Picture File",
		"bmp"=>"Bitmap Image",
		"gif"=>"Graphical Interchange Format File",
		"jpeg"=>"JPEG Image File",
		"jpg"=>"JPEG Image File",
		"png"=>"Portable Network Graphic",
		"psd"=>"Photoshop Document",
		"psp"=>"Paint Shop Pro Image File",
		"tif"=>"Tagged Image File Format",
		"ai"=>"Adobe Illustrator File",
		"drw"=>"Drawing File",
		"dxf"=>"Drawing Exchange Format",
		"eps"=>"Encapsulated PostScript",
		"ps"=>"PostScript File",
		"svg"=>"Scalable Vector Graphics",
		"3dm"=>"Rhino 3D Model",
		"3dmf"=>"QuickDraw 3D Metafile",
		"indd"=>"Adobe InDesign File",
		"pdf"=>"Portable Document Format File",
		"qxd"=>"QuarkXpress Document",
		"qxp"=>"QuarkXpress 6 Project File",
		"aac"=>"Advanced Audio Coding File",
		"aif"=>"Audio Interchange File Format",
		"iff"=>"Interchange File Format",
		"m3u"=>"Media Playlist File",
		"mid"=>"MIDI File",
		"midi"=>"MIDI File",
		"mp3"=>"MP3 Audio File",
		"mpa"=>"MPEG Audio File",
		"ra"=>"Real Audio",
		"ram"=>"Real Audio Media",
		"wav"=>"Windows WAVE Sound File",
		"wma"=>"Windows Media Audio File",
		"3gp"=>"3GPP Multimedia File",
		"asf"=>"Advanced Systems Format File",
		"asx"=>"Microsoft ASF Redirector File",
		"avi"=>"Audio Video Interleave File",
		"mov"=>"Apple QuickTime Movie",
		"mp4"=>"MPEG-4 Video File",
		"mpg"=>"MPEG Video File",
		"qt"=>"Apple QuickTime Movie",
		"rm"=>"Real Media File",
		"swf"=>"Macromedia Flash Movie",
		"wmv"=>"Windows Media Video File",
		"asp"=>"Active Server Page",
		"css"=>"Cascading Style Sheet",
		"htm"=>"Hypertext Markup Language File",
		"html"=>"Hypertext Markup Language File",
		"js"=>"JavaScript File",
		"jsp"=>"Java Server Page",
		"php"=>"Hypertext Preprocessor File",
		"xhtml"=>"Extensible Hypertext Markup Language",
		"fnt"=>"Font File",
		"fon"=>"Generic Font File",
		"otf"=>"OpenType Font",
		"ttf"=>"TrueType Font",
		"8bi"=>"Photoshop Plug-in",
		"plugin"=>"Mac OS X Application Plug-in",
		"xllv"=>"Excel Add-In File",
		"cab"=>"Windows Cabinet File",
		"cpl"=>"Windows Control Panel",
		"dmp"=>"Windows Memory Dump",
		"drv"=>"Device Driver",
		"ini"=>"Initialization File",
		"key"=>"Security Key",
		"sys"=>"System File",
		"cfg"=>"Configuration File",
		"msi"=>"Windows Installer File",
		"reg"=>"Registration Information",
		"app"=>"Mac OS X Application",
		"bat"=>"DOS Batch File",
		"cgi"=>"Common Gateway Interface Script",
		"com"=>"Command File",
		"exe"=>"Executable File",
		"pif"=>"Program Information File",
		"vb"=>"VBScript File",
		"ws"=>"Windows Script",
		"gz"=>"Gnu Zipped File",
		"pkg"=>"Mac OS X Installer Package",
		"rar"=>"WinRAR Compressed Archive",
		"sea"=>"Self-Extracting Archive",
		"sit"=>"Stuffit Archive",
		"sitx"=>"Stuffit X Archive",
		"zip"=>"Zipped File",
		"bin"=>"Macbinary II Encoded File",
		"hqx"=>"BinHex 4.0 Encoded File",
		"mim"=>"Multi-Purpose Internet Mail Message",
		"uue"=>"Uuencoded File",
		"c"=>"C/C++ Source Code File",
		"cpp"=>"C++ Source Code File",
		"java"=>"Java Source Code File",
		"pl"=>"Perl Script",
		"bak"=>"Backup File",
		"gho"=>"Norton Ghost Backup File",
		"old"=>"Backup File",
		"ori"=>"Original File",
		"tmp"=>"Temporary File",
		"dmg"=>"Mac OS X Disk Image",
		"iso"=>"Disc Image File",
		"vcd"=>"Virtual CD",
		"gam"=>"Saved Game File",
		"nes"=>"Nintendo (NES) ROM File",
		"rom"=>"NES Game ROM",
		"sav"=>"Saved Game",
		"lnk"=>"File Shortcut",
		"yps"=>"Yahoo! Messenger Data File"
		);

		$doctype=$ext[$fileext];
		if($doctype=="")
			return $fileext;
		else
			return $doctype;
	}

	//To get superusername
	function superusername()
	{
		global $maildb,$db;
		$que="select username from users where type='sp'";
		$res=mysql_query($que,$db);
		$row=mysql_fetch_row($res);
		return $row[0];
	}

	//increment or decrement the time stamp by n days.
	//eg usage : To add 4 days to the time stamp... $newTimeStamp = getTimeStampByNDays("+4", $yourCurrentTimeStamp);
	//	   To substract 4 days to the time stamp... $newTimeStamp = getTimeStampByNDays("-4", $yourCurrentTimeStamp);
	function getTimeStampByNDays( $nDays, $timeStamp )
	{
		$pos = strpos($nDays, "+");
		if( $pos === false ) {
			$pos = strpos($nDays, "-");
			if ( $pos === false ) {
				$nDays = "+". $nDays;
			}
		}
		return strtotime($nDays." days", $timeStamp);
	}


	//Url Encoding Symboles
	function RenameUploadedFile($filename)
 	{
 		$fname=str_replace("&","|*amp*|",$filename);
 		return $fname;
 	}

	//Generate the random number
	function generatePin($type)
 	{
 	  if($type=="num")
 	  	   $randNum=md5(rand(2,99999999));
 	  else
 	       $randNum=md5(rand(2,99999999));

 	  return substr($randNum,4,4);
 	}

	//Display The schedule select box times
	function display_SelectBox_Times()
	{

		$Timeoptions="<option value=''>&nbsp;--Select--&nbsp;</option>";
		$Timeoptions.="<option value='24:00'>12:00am</option>";
		$Timeoptions.="<option value='0:30'>12:30am</option>";

		for($i=1,$j=1;$i<24;$i=($i+0.5),$j++)
		{

			$tmin=($j%2==0)?'30':'00';
			$Intval=(int)$i;
			$disptime=$Intval.":".$tmin;
			$am=($i>=12)?"pm":"am";
			$Dt=(($Intval>=13)?($Intval-12):$Intval).":".$tmin;
			$Timeoptions.="<option value='".$disptime."'>".$Dt.$am."</option>";

		}
		return $Timeoptions;
	}

	//Function for Calculating the billing Information
	function comm_calculate($ArgString)
	{
		//$ArgString==type|payrate_margin|billrate_margin|burden|margin|Secondtype|payrate_markup|billrate_markup|markup;
		$ArgString_array=explode("|",$ArgString);
		$Firsttype=trim($ArgString_array[0]);
		$payrate_margin=trim($ArgString_array[1]);
		$billrate_margin=trim($ArgString_array[2]);
		$burden=trim($ArgString_array[3]);
		$margin=trim($ArgString_array[4]);
		$Secondtype=trim($ArgString_array[5]);
		$payrate_markup=trim($ArgString_array[6]);
		$billrate_markup=trim($ArgString_array[7]);
		$markup=trim($ArgString_array[8]);
		$calculateVal="";
		$assignValto="";
		$margincost="";
		$calculateVal1="";
		$assignValto1="";

		if($Firsttype=='margin')
		{
			if($burden!="")
			{
				if($payrate_margin!="" && $billrate_margin!="")
				{
					$calculateVal=((($billrate_margin - ($payrate_margin + (($burden/100) * $payrate_margin)))/$billrate_margin)*100);
					$margincost=($billrate_margin-($payrate_margin +(($burden/100)*$payrate_margin)));
					$assignValto="margin";
				}
				else if($payrate_margin!="" && $billrate_margin=="" && $margin!="")
				{
				   $calculateVal=((($payrate_margin + (($burden/100) * $payrate_margin))*100) / (100 - $margin));
				   $margincost=($calculateVal-($payrate_margin +(($burden/100)*$payrate_margin)));
					$assignValto="billrate";
				}
				else if($payrate_margin=="" && billrate_margin!="" && $margin!="")
				{
					$calculateVal=(($billrate_margin - (($margin/100) * $billrate_margin )) / (1+($burden/100)));
					$margincost=($billrate_margin-($calculateVal +(($burden/100)*$calculateVal)));
					$assignValto="payrate";
				}
			}
		}
		if($Secondtype=='markup')
		{
			if($payrate_markup!='' && $billrate_markup!="")
			{
				$calculateVal1=(($billrate_markup - $payrate_markup) / $payrate_markup)*100;
				$assignValto1="markup";
			}
			else if($payrate_markup!='' && $billrate_markup=="" && $markup!="")
			{
				$calculateVal1=$payrate_markup + ($payrate_markup * $markup/100 );
				$assignValto1="billrate";
			}
			else if($payrate_markup=='' && $billrate_markup!="" && $markup!="")
			{
				$calculateVal1=$billrate_markup/(1 + (1 * $markup/100 ));
				$assignValto1="payrate";
			}
		}
		$margincost=round($margincost,2);
		return($Firsttype."|".round($calculateVal,2)."|".$assignValto."|".$margincost."|".$Secondtype."|".round($calculateVal1,2)."|".$assignValto1);
	}

	//Added a function for downloading the files
	function DownLoadFile($fname,$content,$prop,$dynName=TRUE)
	{
		global $WDOCUMENT_ROOT;

		require("class.MimeType.inc");
		$mime = new MimeType();

		$ftype="";
		$len=0;
		$fname = stripslashes($fname);

		$prop_array=explode("|",$prop);
		$prop_array[0] = ($prop_array[0] == "") ? "application/octet-stream" : $prop_array[0];
		$len=(count($prop_array)>1) ? $prop_array[1] : 0;

		$ftype=$mime->getMimeType($fname);
		$ftype=($ftype == "") ? $prop_array[0] : $ftype;

		Header("Pragma: public");
		Header("Expires: 0");
		Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		Header("Cache-Control: public");

		Header('Content-Type: "'.$ftype.'"; name="'.$fname.'"');
		Header("Content-Description: File Transfer");

		Header('Content-Disposition: attachment; filename="'.ReplaceUrlencodeChar($fname).'"');
		Header("Content-Transfer-Encoding: binary");
		
		print $content;
	}

	//Added a function for downloading inline images with in email body
	function DownLoadInlineImage($fname,$content,$prop)
	{
		global $WDOCUMENT_ROOT;

		$fname = stripslashes($fname);
		$prop_array=explode("|",$prop);
		$ftype=(trim($prop_array[0])!="") ? $prop_array[0] : "image/jpg";

		Header('Content-Type: "'.$ftype.'"; name="'.$fname.'"');
		Header("Content-Description: File Transfer");
		Header('Content-Disposition: attachment; filename="'.ReplaceUrlencodeChar($fname).'"');
		Header("Content-Transfer-Encoding: binary");

		print $content;
	}

		//Function for Calculating the billing Information
	function comm_calculate_Active($ArgString)
	{
		//$ArgString==ActiveTab|payrate|billrate|burden|margin|markup;
		$ArgString_array=explode("|",$ArgString);
		$actType=trim($ArgString_array[0]);
		$payrate=trim($ArgString_array[1]);
		$billrate=trim($ArgString_array[2]);
		$burden=trim($ArgString_array[3]);
		$margin=trim($ArgString_array[4]);
		$markup=trim($ArgString_array[5]);
		$bill_burden=trim($ArgString_array[6]);

                if($burden == ""){
                    $burden = 0;
                }

                if($bill_burden != ""){
                    $bill_burden_amount = ($bill_burden/100)*$billrate;
                }  else {
                    $bill_burden_amount = 0;
                }

//		if($burden!="")
//		{
                    if($payrate!="" && $billrate!="")
                    {
                        $margin=(((($billrate-$bill_burden_amount) - ($payrate + (($burden/100) * $payrate)))/($billrate-$bill_burden_amount))*100);
                    }
                    else if($payrate!="" && $billrate=="" && $margin!="")
                    {
                        $billrate=((($payrate + (($burden/100) * $payrate))*100) / (100 - $margin));
                    }
                    else if($payrate=="" && $billrate!="" && $margin!="")
                    {
                        $payrate=(($billrate - (($margin/100) * $billrate )) / (1+($burden/100)));
                    }
                    $markup=(($billrate - $payrate) / $payrate)*100;
//		}
//		else
//		{
//			if($payrate!="" && $billrate!="")
//			{
//				$markup=(($billrate - $payrate) / $payrate)*100;
//			}
//			else if($payrate!='' && $billrate=="" && $markup!="")
//			{
//				$billrate=$payrate + ($payrate * $markup/100 );
//			}
//			else if($payrate=='' && $billrate!="" && $markup!="")
//			{
//				$payrate=$billrate/(1 + (1 * $markup/100 ));
//			}
//		}
	 return $actType."|".round($payrate,2)."|".round($billrate,2)."|".round($burden,2)."|".round($margin,2)."|".round($markup,2);
	}
	/*Funtion For Email Assocaition Added By Raghu*/
	function association($associate_conid)
	{

		$con_sno="";
		$com_sno="";
		$cand_sno="";
		$job_sno="";
		$con_result_target=="";
		$com_result_target=="";
		$cand_result_target=="";
		$job_result_target=="";
		$tot_target="";
		$con_str_sno="";
		$com_str_sno="";
		$job_str_sno="";
		$cand_str_sno="";
		$tot_target_sno="";
		//$tot_target=$con_result_target."|".$com_result_target."|".$cand_result_target."|".$job_result_target;

		$que="select con_id from cmngmt_pr where sno='".$associate_conid."'";
		$res=mysql_query($que,$db);
		$row=mysql_fetch_row($res);
		$ass_con_ids=explode(",",$row[0]);
		for($i=0;$i<count($ass_con_ids);$i++)
		 {
			 if(substr_compare($ass_con_ids[$i], "oppr", 0, 4)==0)
			 {
				 if($con_sno=="")
					 $con_sno=substr( $ass_con_ids[$i], 4,(strlen($ass_con_ids[$i])-4));
				 else
					 $con_sno=$con_sno.",".substr( $ass_con_ids[$i], 4,(strlen($ass_con_ids[$i])-4));
			 }
			  if(substr_compare($ass_con_ids[$i], "com", 0, 3)==0)
			 {
				 if($com_sno=="")
					 $com_sno=substr( $ass_con_ids[$i], 3,(strlen($ass_con_ids[$i])-3));
				 else
					 $com_sno=$com_sno.",".substr( $ass_con_ids[$i], 3,(strlen($ass_con_ids[$i])-3));
			 }
			  if(substr_compare($ass_con_ids[$i], "req", 0, 3)==0)
			 {
				 if($job_sno=="")
					 $job_sno=substr($ass_con_ids[$i], 3,(strlen($ass_con_ids[$i])-3));
				 else
					 $job_sno=$job_sno.",".substr( $ass_con_ids[$i], 3,(strlen($ass_con_ids[$i])-3));
			 }
			if(substr_compare($ass_con_ids[$i], "cand", 0, 4)==0)
			 {
				 if($cand_sno=="")
					 $cand_sno=substr( $ass_con_ids[$i], 4,(strlen($ass_con_ids[$i])-4));
				 else
					 $cand_sno=$cand_sno.",".substr( $ass_con_ids[$i], 4,(strlen($ass_con_ids[$i])-4));
			 }


		 }
		if($con_sno!="")
		 {
			$con_str="";
			$con_str_sno="";
						$query_assoc_email="SELECT trim(staffoppr_contact.sno) sno,trim(staffoppr_contact.fname) fname,trim(staffoppr_contact.mname) mname,trim(staffoppr_contact.lname) lname,staffoppr_cinfo.cname cname FROM staffoppr_contact LEFT JOIN staffoppr_cinfo ON staffoppr_contact.csno=staffoppr_cinfo.sno WHERE staffoppr_contact.sno IN ($con_sno) ORDER BY staffoppr_contact.fname";
						$res_assoc_email=mysql_query($query_assoc_email,$db);
						$num_rows=mysql_num_rows($res_assoc_email);

		 while($fetch_assoc_email=mysql_fetch_array($res_assoc_email))
				{
						$sno="oppr".trim($fetch_assoc_email["sno"]);
						$fname=ucwords(trim($fetch_assoc_email["fname"]));
						$mname=ucwords(trim($fetch_assoc_email["mname"]));
						$lname=ucwords(trim($fetch_assoc_email["lname"]));

						$cname=ucwords(trim($fetch_assoc_email["cname"]));

						$name=concate_space($fname,$mname);
						$name=concate_space($name,$lname);

						$name=trim($name);

						if($cname!="" && $name!="")
							$disp_result="<b>".$name."</b>(".$cname.")";
						else if($cname!="")
							$disp_result="(".$cname.")";
						else
							$disp_result="<b>".$name."</b>";

						  if($con_str=="")
							  $con_str=$disp_result;
						  else
							 $con_str=$con_str."|".$disp_result;
						  if($con_str_sno=="")
							 $con_str_sno=$sno."^".$disp_result;
						  else
							 $con_str_sno=$con_str_sno."|".$sno."^".$disp_result;

			  }
					 $con_result_target=str_replace("<b>","", $con_str); // value for, add to target list
					$con_result_target=str_replace("</b>","",$con_result_target);
					 $tot_target_sno=$con_str_sno;
		 }

		 if($com_sno!="")
		 {
					$com_str="";
					$query_assoc_email="SELECT trim(staffoppr_cinfo.sno) sno,cname,trim(address1) address1,trim(address2) address2,trim(city) city,trim(state) state FROM staffoppr_cinfo WHERE sno IN ($com_sno)  ORDER BY cname";

					$res_assoc_email=mysql_query($query_assoc_email,$db);
					$num_rows=mysql_num_rows($res_assoc_email);
					while($fetch_assoc_email=mysql_fetch_array($res_assoc_email))
				{
					$sno="com".trim($fetch_assoc_email["sno"]);
					$cname=ucwords(trim($fetch_assoc_email["cname"]));
					$address1=ucwords(trim($fetch_assoc_email["address1"]));
					$address2=ucwords(trim($fetch_assoc_email["address2"]));
					$city=ucwords(trim($fetch_assoc_email["city"]));
					$state=ucwords(trim($fetch_assoc_email["state"]));

					$address=concate_comma($address1,$address2);
					$address=concate_comma($address,$city);
					$address=concate_comma($address,$state);


					if($cname!="" && $address!="")
						$disp_result="<b>".$cname."</b> - ".$address;
					else
						if($cname!="")
							$disp_result="<b>".$cname."</b>";
						else
							$disp_result=$address;

					if($com_str=="")
						$com_str=$disp_result;
					else
						$com_str=$com_str."|".$disp_result;
					if($com_str_sno=="")
						$com_str_sno=$sno."^".$disp_result;
					else
						$com_str_sno=$com_str_sno."|".$sno."^".$disp_result;

			}
			$com_result_target=str_replace("<b>","", $com_str); // value for, add to target list
			$com_result_target=str_replace("</b>","",$com_result_target);
			if($tot_target_sno!="")
			$tot_target_sno=$tot_target_sno."|".$com_str_sno;
		    else
			$tot_target_sno=$com_str_sno;
		 }

		 if($job_sno!="")
		 {
			$job_str="";

					$query_assoc_email="SELECT trim(posdesc.posid) sno,posdesc.postitle postitle,staffoppr_cinfo.cname cname,trim(staffoppr_cinfo.address1) address1,trim(staffoppr_cinfo.address2) address2,trim(staffoppr_cinfo.city) city,trim(staffoppr_cinfo.state) state FROM posdesc LEFT JOIN staffoppr_cinfo ON posdesc.company=staffoppr_cinfo.sno WHERE posdesc.posid IN ($job_sno) ORDER BY posdesc.postitle";
					$res_assoc_email=mysql_query($query_assoc_email,$db);
					$num_rows=mysql_num_rows($res_assoc_email);
					while($fetch_assoc_email=mysql_fetch_array($res_assoc_email))
				{
					$sno="req".trim($fetch_assoc_email["sno"]);
					$postitle=ucwords(trim($fetch_assoc_email["postitle"]));
					$cname=ucwords(trim($fetch_assoc_email["cname"]));
					$address1=ucwords(trim($fetch_assoc_email["address1"]));
					$address2=ucwords(trim($fetch_assoc_email["address2"]));
					$city=ucwords(trim($fetch_assoc_email["city"]));
					$state=ucwords(trim($fetch_assoc_email["state"]));



					$address=concate_comma($address1,$address2);
					$address=concate_comma($address,$city);
					$address=concate_comma($address,$state);

					if($postitle!="")
						if($cname!="")
							if($address!="")
								$disp_result="<b>".$postitle."</b>(".$cname.") - ".$address;
							else
								$disp_result="<b>".$postitle."</b>(".$cname.")";
						else
							if($postitle!="" && $address!="")
								$disp_result="<b>".$postitle."</b> - ".$address;
							else
								$disp_result="<b>".$postitle."</b>";
					if($job_str=="")
						$job_str=$disp_result;
					else
						$job_str=$job_str."|".$disp_result;
					if(job_str_sno=="")
						$job_str_sno=$sno."^".$disp_result;
					else
						$job_str_sno=$job_str_sno."|".$sno."^".$disp_result;


			}
			$job_result_target=str_replace("<b>","", $job_str); // value for, add to target list
			$job_result_target=str_replace("</b>","",$job_result_target);
			if($tot_target_sno!="")
			$tot_target_sno=$tot_target_sno."|".$job_str_sno;
		    else
			$tot_target_sno=$job_str_sno;
		 }
		 if($cand_sno!="")
		 {
			$cand_str="";

					$query_assoc_email="SELECT trim(candidate_general.sno) sno,trim(fname) fname,trim(mname) mname,trim(lname) lname,profiletitle,trim(address1) address1,trim(address2) address2,trim(city) city,trim(state) state FROM candidate_general WHERE  candidate_general.sno IN($cand_sno) ORDER BY fname";
					$res_assoc_email=mysql_query($query_assoc_email,$db);
					$num_rows=mysql_num_rows($res_assoc_email);
					while($fetch_assoc_email=mysql_fetch_array($res_assoc_email))
				{

					$sno="cand".trim($fetch_assoc_email["sno"]);
					$fname=ucwords(trim($fetch_assoc_email["fname"]));
					$mname=ucwords(trim($fetch_assoc_email["mname"]));
					$lname=ucwords(trim($fetch_assoc_email["lname"]));
					$profiletitle=ucwords(trim($fetch_assoc_email["profiletitle"]));
					$address1=ucwords(trim($fetch_assoc_email["address1"]));
					$address2=ucwords(trim($fetch_assoc_email["address2"]));
					$city=ucwords(trim($fetch_assoc_email["city"]));
					$state=ucwords(trim($fetch_assoc_email["state"]));

					$name=concate_space($fname,$mname);
					$name=concate_space($name,$lname);

					$address=concate_comma($address1,$address2);
					$address=concate_comma($address,$city);
					$address=concate_comma($address,$state);

					if($name!="")
						if($profiletitle!="")
							if($address!="")
								$disp_result="<b>".$name."</b>(".$profiletitle.") - ".$address;
							else
								$disp_result="<b>".$name."</b>(".$profiletitle.")";
						else
							if($name!="" && $address!="")
								$disp_result="<b>".$name."</b> - ".$address;
							else
								$disp_result="<b>".$name."</b>";

				   if($cand_str=="")
						$cand_str=$disp_result;
					else
						$cand_str=$cand_str."|".$disp_result;
					if($cand_str_sno=="")
						$cand_str_sno=$sno."^".$disp_result;
					else
						$cand_str_sno=$cand_str_sno."|".$sno."^".$disp_result;

			}
			$cand_result_target=str_replace("<b>","", $cand_str); // value for, add to target list
			$cand_result_target=str_replace("</b>","",$cand_result_target);
			if($tot_target_sno!="")
			$tot_target_sno=$tot_target_sno."|".$cand_str_sno;
		    else
			$tot_target_sno=$cand_str_sno;
		 }
			if($con_result_target!="")
				{
				  $tot_target=$con_result_target;

				}
			if($com_result_target!="")
				{
					if($tot_target!="")
					$tot_target=$tot_target."|".$com_result_target;
				    else
					 $tot_target=$com_result_target;
				}
			if($cand_result_target!="")
				{
					if($tot_target!="")
					$tot_target=$tot_target."|".$cand_result_target;
					else
					$tot_target=$cand_result_target;
				}
			if($job_result_target!="")
				{
					if($tot_target!="")
					$tot_target=$tot_target."|".$job_result_target;
					else
					$tot_target=$job_result_target;
				}


				$tot_result_target=str_replace("<b>","", $tot_target_sno); // value for, add to target list
				$tot_result_target_temp=str_replace("</b>","",$tot_result_target);

	return $tot_target."^^|^^".$tot_result_target_temp;
	}
	function concate_space($var1,$var2) // to concate varaibles with space if those are not null added by raghu
	{
		if($var1!="" && $var2!="")
			return $var1." ".$var2;
		else
			if($var1!="")
				return $var1;
			else
				return $var2;
	}
	function concate_comma($var1,$var2) // to concate varaibles with comma if those are not null added by raghu
	{
		if($var1!="" && $var2!="")
			return $var1.",".$var2;
		else
			if($var1!="")
				return $var1;
			else
				return $var2;
	}

	// Company Division showing details
	function getCompDivisionAddress($cname,$addr1,$addr2,$city,$state,$country,$zip)
	{
		  $addr="";
		  $Addr_array=array();
		  array_push($Addr_array,trim($addr1),trim($addr2),trim($city),trim($state),trim($country));
		  foreach($Addr_array as $key=>$val)
		  {
			if(empty($val)) unset($Addr_array[$key]);
			}
		  $Addr_array=array_values($Addr_array);
		  $Addr_String=trim(implode(", ",array_values($Addr_array)));
		  $Addr_String.=(trim($zip)!='')?' '.trim($zip):'';
		  $addr=$cname.((trim($cname)!='' && $Addr_String!='')?' - ':'').trim($Addr_String);
		  $addr=(trim($addr)!="")?trim($addr):"no address";
		  return  $addr;
	}
	function getbillingContact($val1)
	{
		global $maildb,$db;
		$All_Contacts="select IF((fname='' AND mname='' AND lname=''),nickname,concat_ws(' ',fname,mname,lname)),ytitle,address1,address2,city,state,wphone from staffoppr_contact  where sno='".$val1."'  AND crmcontact='Y' AND  status='ER' AND (FIND_IN_SET('".$username."',accessto)>0 or owner='".$username."' or accessto='ALL') ";
	    $All_Contcats_Res1=mysql_query($All_Contacts,$db);
	    $bill_res=mysql_fetch_row($All_Contcats_Res1);

		$bill_cont=array();
		$bill_addr=array();

		if($bill_res[0]!="")
			array_push($bill_cont,$bill_res[0]);
		if($bill_res[1]!="")
			array_push($bill_cont,$bill_res[1]);

		if($bill_res[2]!="")
			array_push($bill_addr,$bill_res[2]);
		if($bill_res[3]!="")
			array_push($bill_addr,$bill_res[3]);
		if($bill_res[4]!="")
			array_push($bill_addr,$bill_res[4]);
		if($bill_res[5]!="")
			array_push($bill_addr,$bill_res[5]);
		$bil_add = implode(",",$bill_addr);

		if($bil_add !="")
			array_push($bill_cont,$bil_add);

		if($bill_res[6]!="")
			array_push($bill_cont,$bill_res[6]);

		$final_res = implode("|",$bill_cont);
		return $final_res;
	}
	function getbillingaddress($val1)
	{
		global $maildb,$db;
		$Query_Comp="select address1,address2,city,state from staffoppr_cinfo where sno='".$val1."'";
		$Result_Comp=mysql_query($Query_Comp,$db);
		$Res_comp=mysql_fetch_row($Result_Comp);

		$Comp_Array=array();
		$Comp_Array1=array();
		$Comp_Array2=array();

		if(trim($Res_comp[0])!="")
			array_push($Comp_Array,$Res_comp[0]);
		if(trim($Res_comp[1])!="")
			array_push($Comp_Array,$Res_comp[1]);

		$Array_Comp=implode(" ",$Comp_Array);

		if(trim($Res_comp[2])!="")
			array_push($Comp_Array1,$Res_comp[2]);
		if(trim($Res_comp[3])!="")
			array_push($Comp_Array1,$Res_comp[3]);

		$Array1_Comp=implode(" " ,$Comp_Array1);

		if($Array_Comp !="")
			array_push($Comp_Array2,$Array_Comp);
		if($Array1_Comp!="")
			array_push($Comp_Array2,$Array1_Comp);

		$Array2_Comp=implode("," ,$Comp_Array2);
		return $Array2_Comp;
	}
	function formercompanies($content)
	{
		global $maildb,$db;
		if($content != "")
		{
			$dat="";
			$fcomp = explode(",",$content);
			if(!empty($fcomp))
			{
				for($i=0;$i<count($fcomp);$i++)
				{
					$sql="select cname from staffoppr_cinfo where sno=".$fcomp[$i];
					$fcompa=mysql_query($sql,$db);
					$result=mysql_fetch_row($fcompa);
					$dat.=$result[0].",";
				}
				$dat = substr($dat,0,-1);
			}
			return $dat;
		}
		else
		{
			$dat = "";
			return $dat;
		}
	}
	function getparking($val1,$val2)
	{
		$park_ing1=explode("|",$val1);
		$p_rate=$val2;
		if($park_ing1[5]=="prate")
			$park_ing1[5]="Rate";
		if($park_ing1[5]!="" && $p_rate!="")
			$prate=$park_ing1[5]."($".$p_rate.")";
		if($park_ing1[5]!="" && $p_rate=="")
			$prate=$park_ing1[5];
		if($park_ing1[5]=="" && $p_rate!="")
			$prate="($".$p_rate.")";

		if($park_ing1[3]=="lspaces")
			$park_ing1[3]="Limited Spaces";
		if($park_ing1[4]=="pspaces")
			$park_ing1[4]="Plenty of Spaces";

		$park_check=ucwords($park_ing1[0])."-".ucwords($park_ing1[1])."-".ucwords($park_ing1[2])."-".ucwords($park_ing1[3])."-".	ucwords($park_ing1[4])."-".$prate."-".ucwords($park_ing1[6])."-".ucwords($park_ing1[7]);

		$mod_park = trim(str_replace("--","-",str_replace("--","-",str_replace("--","-",ereg_replace("---","--",$park_check)))),'-');
		return $mod_park;
	}

	//Function to get the sno based on the manage name and type of the status
 	function getManageSno($name,$type)
 	{
 		global $maildb,$db;
 		$sql="SELECT sno FROM manage WHERE name='".$name."' AND type='".$type."'";
 		$res=mysql_query($sql,$db);
 		$fetch=mysql_fetch_row($res);
 		return($fetch[0]);
 	}

 	//Function for checking the update query with single input condition(in contacts,compnaies,candidates)
    function Check_Update_Qry($CondVal,$updateQry)
    {
        global $maildb,$db,$username;
        $retSql="";
        if(trim($CondVal)!="")
            $retSql=mysql_query($updateQry,$db);

        return $retSql;
    }

	//Url Encoding Symboles
	function ReplaceUrlencodeChar($srting)
 	{
 		$srting=str_replace(";","%3B",$srting);
 		return $srting;
 	}

	//Updating the tasklist table
	function UpdateTaskList($con_id,$current_module_name)
	{
		global $maildb,$db;

		//For CRM->Companies
		if(strpos("*".$con_id,"com")>0)
		{
			$query="select CONCAT_WS(' ',cname,IF(phone='--' OR phone='---' OR phone='----', '',phone)) from staffoppr_cinfo where sno='".substr($con_id,3)."'";
            $result=mysql_query($query,$db);
            $fetchrow=mysql_fetch_row($result);
            $regarding=$fetchrow[0];

            //Function for updating Refarding field in tasklist table.
            updateTaskRegarding($regarding,$con_id);
		}
		//For CRM->Contacts
		else if(strpos("*".$con_id,"oppr")>0)
		{
			$query="select CONCAT_WS(' ',fname,mname,lname,IF(wphone='--' OR wphone='---' OR wphone='----', '',wphone)) from staffoppr_contact where sno='".substr($con_id,4)."'";
            $result=mysql_query($query,$db);
            $fetchrow=mysql_fetch_row($result);
            $regarding=$fetchrow[0];

            //Function for updating Refarding field in tasklist table.
            updateTaskRegarding($regarding,$con_id);
		}
		//For CRM->Joborders
		else if(strpos("*".$con_id,"req")>0)
		{
			$query="select CONCAT_WS(' ',posdesc.postitle,staffoppr_cinfo.cname) from posdesc LEFT JOIN staffoppr_cinfo ON staffoppr_cinfo.sno=posdesc.company and (posdesc.company!='' or posdesc.company!='0') where posdesc.posid='".substr($con_id,3)."'";
            $result=mysql_query($query,$db);
            $fetchrow=mysql_fetch_row($result);
            $regarding=$fetchrow[0];

            //Function for updating Refarding field in tasklist table.
            updateTaskRegarding($regarding,$con_id);
		}
		//For CRM->Candidates
		else if(strpos("*".$con_id,"cand")>0)
		{
			$query="select CONCAT_WS(' ',fname,mname,lname,IF(hphone='--' OR hphone='---' OR hphone='----', '',hphone)) from candidate_general where username='".$con_id."'";
            $result=mysql_query($query,$db);
            $fetchrow=mysql_fetch_row($result);
            $regarding=$fetchrow[0];

            //Function for updating Refarding field in tasklist table.
            updateTaskRegarding($regarding,$con_id);
		}
		//For Accounting->Customers & CRM->Active Clients
		else if(strpos("*".$con_id,"acc")>0)
		{
			$query="select CONCAT_WS(' ',cname,address1,address2) from staffacc_cinfo where username='".$con_id."'";
            $result=mysql_query($query,$db);
            $fetchrow=mysql_fetch_row($result);
            $regarding=$fetchrow[0];

            //Function for updating Refarding field in tasklist table.
            updateTaskRegarding($regarding,$con_id);
		}
		//For HRM->Applicant Tracking & Overseas Recruitment
		else if(strpos("*".$con_id,"con")>0 && ($current_module_name=="HRM->OR" || $current_module_name=="HRM->AT"))
		{
			$query="select CONCAT_WS(' ',fname,mname,lname,IF(hphone='--' OR hphone='---' OR hphone='----', '',hphone)) from consultant_general where username='".$con_id."'";
            $result=mysql_query($query,$db);
            $fetchrow=mysql_fetch_row($result);
            $regarding=$fetchrow[0];

            //Function for updating Refarding field in tasklist table.
            updateTaskRegarding($regarding,$con_id);
		}
		//For HRM->Employee_Mngmt
		else if(strpos("*".$con_id,"emp")>0)
		{
			$query="select CONCAT_WS(' ',fname,mname,lname,IF(wphone='--' OR wphone='---' OR wphone='----', '',wphone)) from emp_list,hrcon_general where emp_list.username=hrcon_general.username AND hrcon_general.ustatus = 'active' and  emp_list.sno='".substr($con_id,3)."'";
            $result=mysql_query($query,$db);
            $fetchrow=mysql_fetch_row($result);
            $regarding=$fetchrow[0];

            //Function for updating Refarding field in tasklist table.
            updateTaskRegarding($regarding,$con_id);
		}
		//For HRM->Hiring_Mngmt
		else if(strpos("*".$con_id,"app")>0 || strpos("*".$con_id,"con")>0)
		{
			$query="select CONCAT_WS(' ',fname,mname,lname,IF(wphone='--' OR wphone='---' OR wphone='----', '',wphone)) from hrcon_general where username='".$con_id."' AND hrcon_general.ustatus = 'active'";
            $result=mysql_query($query,$db);
            $fetchrow=mysql_fetch_row($result);
            $regarding=$fetchrow[0];

            //Function for updating Refarding field in tasklist table.
            updateTaskRegarding($regarding,$con_id);
		}
		//For ADMIN->Contact_Mngmt
		else if(strpos("*".$con_id,"addr")>0)
		{
			$query="select CONCAT_WS(' ',fname,mname,lname,IF(wphone='--' OR wphone='---' OR wphone='----', '',wphone)) from contacts where serial_no='".substr($con_id,4)."'";
            $result=mysql_query($query,$db);
            $fetchrow=mysql_fetch_row($result);
            $regarding=$fetchrow[0];

            //Function for updating Refarding field in tasklist table.
            updateTaskRegarding($regarding,$con_id);
		}
		return;
	}

	function displayCurrency($currencyValue,$mod="")
	{
		$currencyArray=array('USD','AED','EUR','ARP','AUD','BEF','BRR','CAD','CHF','CNY','CSK','DEM','ESP','FJD',' FRF','GBP','GRD','HKD','HUF','IDR','IEP','ILS','INR','ITL','JPY','KRW','MXP','MYR','NLG','NZD','PLZ','RUR','SEK','SGD','TWD','ZAR','LUF','THB','XPF');
		$arrCount=count($currencyArray);
		for($c=0;$c<$arrCount;$c++)
		{
			if(trim($currencyValue)=="" && $currencyArray[$c]=='USD' && $mod=="USD")  
			{
				echo "<option selected value=$currencyArray[$c]>$currencyArray[$c]</option>";
			}
			else
			{
				echo "<option ".compose_sel($currencyArray[$c],$currencyValue)." value=$currencyArray[$c]>$currencyArray[$c]</option>";
			}
		}
	}

	//Function for updating Refarding field in tasklist table.
    function updateTaskRegarding($regarding,$con_id)
    {
        global $maildb,$db;
        //For updating regarding column in tasklist table
        $updTaskListQue="update tasklist set regarding='".addslashes($regarding)."' where contactsno='".$con_id."' and contactsno!=''";
        mysql_query($updTaskListQue,$db);
    }

    //Preparing the assignment ID
    function get_Assginment_Id($sno)
	{
        $startVal=100000;
        return "ASGN".($startVal+$sno);
    }

	// Function to get time for caluclation purpose Ex : if 1 hours 30 mins should take as 1.50
	function getTimeFloat($dhours,$dmin)
	{
		$drem=0;

		if($dmin>=60)
		{
			$dres=intval($dmin/60);
			$drem=$dmin%60;

			if($drem<10)
			{
				$drem="0".$drem;
			}
			$dhours=$dhours+$dres;
		}
		else if($dmin<10)
		{
			$drem="0".$dmin;
		}
		else
		{
			$drem=$dmin;
		}

		$Time_hours = $dhours.".".$drem;

		return $Time_hours;
	}

	//Function to get template Id if u have Customer Id id
	function getTemplateId($cust_id)
	{
		global $maildb,$db;
		$get_template = "SELECT templateid,override_tempid FROM staffacc_cinfo WHERE sno = '".$cust_id."'";
		$res_template = mysql_query($get_template,$db);
		$row_template = mysql_fetch_row($res_template);
		if($row_template[1] != '0')
		{
		return $row_template[1];
		} else {
		return $row_template[0];
		}
	}

	// This Function executes the passed queries and returns the "single Result Set" as an Array
	function queryExecutor($tpl_query)
	{
		global $maildb,$db;
		if(($tpl_query_res = mysql_query($tpl_query,$db)) !== false)
			$tpl_query_info = mysql_fetch_row($tpl_query_res);
		else
			$tpl_query_info = array();
		return $tpl_query_info;
	}

	//Function to get template name if u have template id
	function getTemplateName($template_id)
	{
		global $maildb,$db;

		$get_template = "SELECT TRIM(IF (invtmp_version =0, invtmp_name, concat( invtmp_name, ' (1.', invtmp_version, ')' ) )) versioned_name FROM IT_Template_Name LEFT JOIN Invoice_Template ON Invoice_Template.invtmp_template = IT_Template_Name.invtmp_sno WHERE Invoice_Template.invtmp_sno = '".$template_id."'";
		$res_template = mysql_query($get_template,$db);
		$row_template = mysql_fetch_row($res_template);

		return $row_template[0];
	}

	function getDefaultTemp_Name()            // Function for getting default Invoice Template Name
	{
		 global $maildb,$db;
		 $Tempid_qry="SELECT TRIM(IF (invtmp_version =0, invtmp_name, concat( invtmp_name, ' (1.', invtmp_version, ')' ) )) versioned_name FROM IT_Template_Name A, Invoice_Template B WHERE B.invtmp_template = A.invtmp_sno AND B.invtmp_status='ACTIVE' AND invtmp_default='Y'";
		 $Tempid_res=mysql_query($Tempid_qry,$db);
		 $Tempid_fet=mysql_fetch_row($Tempid_res);
		 return  $Tempid_fet[0];
	}

	function getDefaultTemp_Id()            // Function for getting default Invoice Template Id
	{
		 global $maildb,$db;
		 $Tempid_qry="SELECT invtmp_sno FROM Invoice_Template WHERE invtmp_default='Y'";
		 $Tempid_res=mysql_query($Tempid_qry,$db);
		 $Tempid_fet=mysql_fetch_row($Tempid_res);
		 return  $Tempid_fet[0];
	}
	// Function for getting default Invoice Template Type
	function getDefaultTemp_Type($template_id,$typ='')
	{
		 global $maildb,$db;

		$get_template_type = "SELECT Invoice_Template.inv_tmptype_sno FROM IT_Template_Name LEFT JOIN Invoice_Template ON Invoice_Template.invtmp_template = IT_Template_Name.invtmp_sno WHERE Invoice_Template.invtmp_sno = '".$template_id."'";
		$res_template_type = mysql_query($get_template_type,$db);
		$row_template_type = mysql_fetch_row($res_template_type);

			switch($row_template_type[0]){
				case '1':
					$new_file = 'invoice.php';
					$edit_file = 'editinvoice.php';
				break;
				case '2':
					$new_file = 'invoice.php';
					$edit_file = 'editinvoice.php';
				break;
				case '3':
					$new_file = 'custom_invoice.php';
					$edit_file = 'custom_editinvoice.php';
				break;
				case '4':
					$new_file = 'invoice_timeExp.php';
					$edit_file = 'editinvoice_timeExp.php';
				break;
				case '5':
					$new_file = 'invoice_consolidated.php';
					$edit_file = 'editinvoice_consolidated.php';
				break;
				default:
					$new_file = 'invoice.php';
					$edit_file = 'editinvoice.php';
				break;	
			}
			if($typ == 'new'){
				return $new_file;
			}
			else if($typ == 'edit'){
				return $edit_file;
			}
			else{
				return $row_template_type[0];
			}
		
	}
	// ---------------- Changes of vani-------------------
	//For updating sys admin company_info.totusers
	function updateSysTotalUsers()
	{
		global $maindb,$maildb,$db,$companyuser;

		$totusers = 0;

		$hque="SELECT count(1) FROM users LEFT JOIN sysuser ON users.username=sysuser.username WHERE users.type IN ('sp','PE','consultant') AND users.status!='DA' AND users.usertype!=''";
		$hresUser=mysql_query($hque,$db);
		$arrPowerusers = mysql_fetch_row($hresUser);
		$powerUsers = $arrPowerusers[0];

		$hque="SELECT count(1) FROM users LEFT JOIN sysuser ON users.username=sysuser.username WHERE users.type IN ('sp','PE','consultant') AND users.status!='DA' AND users.usertype='FO'";
		$hresUser=mysql_query($hque,$db);
		$arrPowerusers = mysql_fetch_row($hresUser);
		$foUsers = $arrPowerusers[0];

		$hque="SELECT count(1) FROM users LEFT JOIN sysuser ON users.username=sysuser.username WHERE users.type IN ('sp','PE','consultant') AND users.status!='DA' AND users.usertype='BO'";
		$hresUser=mysql_query($hque,$db);
		$arrPowerusers = mysql_fetch_row($hresUser);
		$boUsers = $arrPowerusers[0];

		$hque="SELECT count(1) FROM users LEFT JOIN sysuser ON users.username=sysuser.username WHERE users.type IN ('sp','PE','consultant') AND users.status!='DA' AND users.usertype='UL'";
		$hresUser=mysql_query($hque,$db);
		$arrPowerusers = mysql_fetch_row($hresUser);
		$ulUsers = $arrPowerusers[0];

		$hque="SELECT count(1) FROM users LEFT JOIN sysuser ON users.username=sysuser.username WHERE users.type IN ('sp','PE','consultant') AND users.status!='DA' AND users.usertype=''";
		$hresUser=mysql_query($hque,$db);
		$arrSelfusers = mysql_fetch_row($hresUser);
		$selfUsersEmp = $arrSelfusers[0];

		$hque="SELECT count(1) FROM users WHERE type='cllacc' AND status!='DA'";
		$hresUser=mysql_query($hque,$db);
		$arrSelfusers = mysql_fetch_row($hresUser);
		$selfUsersComp = $arrSelfusers[0];

		$hque="SELECT count(1) FROM users WHERE type='cand' AND status!='DA'";
		$hresUser=mysql_query($hque,$db);
		$arrSelfusers = mysql_fetch_row($hresUser);
		$applicants = $arrSelfusers[0];

		require("cdatabase.inc");

		$que="update company_info,capp_info set totusers='$powerUsers',fousers='$foUsers',bousers='$boUsers',ulusers='$ulUsers',selfusers='$selfUsersEmp',cselfusers='$selfUsersComp',applicants='$applicants'  where company_info.sno=capp_info.sno and capp_info.comp_id = '$companyuser'";
		mysql_query($que,$maindb);
	}

	//For updating sys admin company_info.selfusers
	function updateSysSelfUsers()
	{
		updateSysTotalUsers();
	}

	// To insert users as contacts of CRM companies of selected companies.
	function insUpdAppCRMContact($data,$varsysuser,$state)
	{
		/*
		global $maindb,$maildb,$db,$companyuser;
		$tempdb=$db;

		$queSysCompSno = "select sno from capp_info where comp_id = '".$companyuser."'";
		$resSysCompSno = mysql_query($queSysCompSno,$maindb);
		$rowSysCompSno = mysql_fetch_row($resSysCompSno);

		$queSysComp = "select comp_id from capp_info where sno in (select capp_sno from company_crm)";
		$resSysComp = mysql_query($queSysComp,$maindb);
		while($rowSysComp = mysql_fetch_row($resSysComp))
		{
			mysql_select_db($rowSysComp[0],$tempdb);
			$varcond = "";

			$parent_qry = "select sno from staffoppr_cinfo where syscompid='".$rowSysCompSno[0]."'";
			$parent_res = mysql_query($parent_qry,$tempdb);
			$sysCompSno = mysql_fetch_row($parent_res);
			$csno = $sysCompSno[0];

			if($state=='insert')
			{
				$parent_qry = "select sno from staffoppr_contact where sysuserid = '".$varsysuser."' and csno ='".$csno."'";
				$parent_res = mysql_query($parent_qry,$tempdb);
				$parent_rows = mysql_num_rows($parent_res);
				$parent_result = mysql_fetch_row($parent_res);
				if($parent_rows  > 0)
				{
					$queComp = "update staffoppr_contact set fname ='".addslashes($data[0])."', mname='".addslashes($data[1])."', lname='".addslashes($data[2])."', muser=1, mdate=NOW() where sno= $parent_result[0]";
				}
				else
				{
					$parent_qry = "select sno from staffoppr_contact where fname ='".addslashes($data[0])."' and mname='".addslashes($data[1])."' and lname='".addslashes($data[2])."' and sysuserid = 0 and owner=1";
					$parent_res = mysql_query($parent_qry,$tempdb);
					$parent_rows = mysql_num_rows($parent_res);
					$parent_result = mysql_fetch_row($parent_res);
					if($parent_rows  > 0)
						$queComp = "update staffoppr_contact set fname ='".addslashes($data[0])."', mname='".addslashes($data[1])."', lname='".addslashes($data[2])."', muser=1, mdate=NOW(), sysuserid= '".$varsysuser."',csno='".$csno."' where sno= $parent_result[0]";
					else
						$queComp = "insert into staffoppr_contact(sno, fname, mname, lname, csno, approveuser, status, dauser, dadate, stime, accessto, owner, muser, mdate, sysuserid) values('','".addslashes($data[0])."','".addslashes($data[1])."','".addslashes($data[2])."','".$csno."','1','ER','','',NOW(),'ALL','1','1',NOW(),'".$varsysuser."')";
				}
			}
			else
			{
				$queComp = "update staffoppr_contact set fname ='".addslashes($data[0])."', mname='".addslashes($data[1])."', lname='".addslashes($data[2])."', muser=1, mdate=NOW() where sysuserid= '".$varsysuser."' and csno='".$csno."'";
			}
			mysql_query($queComp,$tempdb);

		}
		mysql_select_db($companyuser,$db);
		*/
	}

	// To store data in history tables for backup.
	function insertCompanyHistory($addr)
	{
		global $maindb,$username;

		$que="insert into company_info_his(sno, created_by, company_name, fname, mname, lname, title, address1, address2, city, state, country, zip, email, phone1, phone2, fax, status, cdate, atype, version, port, domain, totusers, bsdate, lbdate, actDate, dadate, comp_status, comp_type, muser, mdate, accnumber, fid, powerprice, selfprice, comp_sno, colfrom) select '', created_by, company_name, fname, mname, lname, title, address1, address2, city, state, country, zip, email, phone1, phone2, fax, status, cdate, atype, version, port, domain, totusers, bsdate, lbdate, actDate, dadate, comp_status, comp_type, '".$username."', now(), accnumber, fid, powerprice, selfprice,sno,'compuser' from company_info where sno='".$addr."'";
		mysql_query($que,$maindb);
		$cid=mysql_insert_id($maindb);

		$que="insert into company_billinfo_his( sno, contact, email, terms, payment, sendmethod, creditcard, expiredate, nameoncard, address1, address2, city, state, country, zip, contracttype) select  ".$cid.", contact, email, terms, payment, sendmethod, creditcard, expiredate, nameoncard, address1, address2, city, state, country, zip, contracttype from company_billinfo where sno='".$addr."'";


		mysql_query($que,$maindb);

	}

	//To update application Admin company Info
	function updateAppAdmCompany($data,$addr)
	{
		global $maildb,$db,$maindb;

		//only one record will be there n=in this table. no need to specify sno to update.
		$que="update company_info set company_name='".addslashes($data[0])."', fname ='".addslashes($data[1])."', mname ='".addslashes($data[2])."', lname ='".addslashes($data[3])."', title ='".addslashes($data[4])."', address1 ='".addslashes($data[5])."', address2 ='".addslashes($data[6])."', city  ='".addslashes($data[7])."', state ='".addslashes($data[8])."', country ='".$data[9]."', zip ='".addslashes($data[10])."', email ='".addslashes($data[11])."', phone1='".addslashes($data[12])."', phone2 ='".addslashes($data[13])."', fax  ='".addslashes($data[14])."', fid ='".addslashes($data[15])."'";
		mysql_query($que,$db);
	}

	//To update application CRM company Info
	function updateAppCRMCompany($data,$addr)
	{
		/*
		global $maindb,$maildb,$db,$companyuser;
		$tempdb=$db;

		$queSysComp = "select comp_id from capp_info where sno in (select capp_sno from company_crm)";
		$resSysComp = mysql_query($queSysComp,$maindb);
		while($rowSysComp = mysql_fetch_row($resSysComp))
		{
			mysql_select_db($rowSysComp[0],$tempdb);

			$que="update staffoppr_cinfo set cname ='".addslashes($data[0])."', address1 ='".addslashes($data[5])."', address2 ='".addslashes($data[6])."', city  ='".addslashes($data[7])."', state ='".addslashes($data[8])."', country ='".$data[9]."', zip ='".addslashes($data[10])."', phone='".addslashes($data[12])."', fax  ='".addslashes($data[14])."', federalid ='".addslashes($data[15])."' where syscompid = '".$addr."' and syscompid !=0";
			mysql_query($que,$tempdb);
		}
		mysql_select_db($companyuser,$tempdb);
		*/
	}

	//to get the manage name  from maindb
	function getManageMain($sno)
	{
		global $maindb;

		$manage_sql="select name from manage where sno=$sno";
		$manage_res=mysql_query($manage_sql,$maindb);
		$manage_Data=mysql_fetch_row($manage_res);

		return $manage_Data[0];
	}

	//function for getting Template id from invoce
	function getInvoiceTemplateId($Invid)
	{
		global $maildb,$db;

	    $Invqry = "select templateid from invoice where sno = ".$Invid;
		$Invres = mysql_query($Invqry,$db);
		$Invrow = mysql_fetch_row($Invres);

		return $Invrow[0];
	}

	//encoding mail subject
	function encodedMailsubject($charset,$data,$Decode='B')
	{
		return $data;

		/*
		if($charset=="")
		{
			return $data;
		}
		else
		{
			$charset = AssignEmailCharset($charset);
			$Decode = ($Decode!='') ? $Decode : 'B';
			$decodeStr = "=?".$charset."?".$Decode."?";

			if($Decode == "B")
				$decodeStr.=base64_encode($data);
			else if($Decode == "Q")
				$decodeStr.=preg_replace("/(=([0-9A-F]){1,1}([0-9A-F]){1,1})/e", "chr(hexdec('\\2'.'\\3'))", $data); //quoted_printable_decode($data);

			return $decodeStr."?=";
		}
		*/
	}

	//Assigning charset
	function AssignEmailCharset($charset)
	{
		$charset = (trim($charset)!='') ? $charset : CONVERT_DEFAULT_MAIL_CHAR;
		return $charset;
	}

	// Updating the total count of the candidates submitted for perticular job order
    function updateTotalCandSubmissionsCount($reqid)
    {
        global $db;

		setGroupConcatMaxLength();

        $candSubQue = "SELECT GROUP_CONCAT(resumeid) FROM reqresponse WHERE par_id=0 AND posid='$reqid'";
        $candSubRes = mysql_query($candSubQue,$db);
        $candSubValue = mysql_fetch_row($candSubRes);

        if($candSubValue[0]!="")
        {
            $subCandCount = count(array_unique(explode(",",$candSubValue[0])));

            $candSubQue = "update posdesc set sub_cand_count='".$subCandCount."' where posid='".$reqid."'";
            mysql_query($candSubQue,$db);
        }
    }

    // Updating the total submissions submitted for perticular job order
    function updateTotalSubmissionsCount($posid)
    {
        global $maildb,$db;

        $que="update posdesc set sub_sub_count=sub_sub_count+1 where posid='".$posid."'";
		mysql_query($que,$db);
    }

    // Updating the total Inquiries got for perticular job order
    function updateTotalInquiriesCount($posid)
    {
        global $maildb,$db;
    	$que="update posdesc set sub_inq_count=sub_inq_count+1 where posid='".$posid."'";
		mysql_query($que,$db);
	}

    // Updating the total no.of interviews setup for perticular job order
    function updateTotalInterviewsCount($posid)
    {
        global $maildb,$db;
        $selReqIDQry ="select count(req_id) FROM resume_history,manage WHERE resume_history.status=manage.sno AND resume_history.req_id='".$posid."' AND manage.type='interviewstatus' AND manage.name='Interview' AND resume_history.type='interview' GROUP BY req_id";
        $selReqIDRes = mysql_query($selReqIDQry,$db);
        $selReqIDRow = mysql_fetch_row($selReqIDRes);
        $countReq   = $selReqIDRow[0];
		$que="update posdesc set posdesc.sub_int_count='".$countReq."' WHERE posdesc.posid='".$posid."'";
        mysql_query($que,$db);
	}

	//to get the company summary address divisions tree in Admin/Datamanagemnet/Comapnies
	function div_addr_Tree_Admin($par,$move,$curSno,$module)
	{
		global $maildb,$db,$username;
		$Sql="select sno,address1,address2,city,state,country,zip,IF(accessto='ALL','Public',IF(accessto='$username','Private',IF(FIND_IN_SET('$username',accessto)>0,'Share','None'))),status,cname from staffoppr_cinfo where  parent='".$par."' order by cname";
		$Res=mysql_query($Sql,$db);
		$Tot_Cols=mysql_num_fields($Res);
		while($Data=mysql_fetch_row($Res))
		{
			$CheckChild_sql="select sno from staffoppr_cinfo where parent=$Data[0]";
			$CheckChild_res=mysql_query($CheckChild_sql,$db);
			$checkrows=mysql_num_rows($CheckChild_res);
			if($checkrows>0)
				$Icon='/BSOS/images/crm/icon-div.gif';
			else
			$Icon='/BSOS/images/crm/icon-branch.gif';
			$divAddr='';
			$divAddr=getCompDivisionAddress($Data[9],$Data[1],$Data[2],$Data[3],$Data[4],getCountry($Data[5]),$Data[6]);
			if($Data[0]==$curSno)

				{

					$DispData="&nbsp;<font  class='crmsummary-contentbolddata1'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
			 	}
			 else
			  {
				if($Data[8]=='INACTIVE')
				{
					$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Admin/Data_Mngmt/Companies/hisreviewmanage.php?addr=$Data[0]&module=$module','compArchieve',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
				}
				else
				{
					$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Admin/Data_Mngmt/Companies/viewcompanySummary.php?addr=$Data[0]&module=$module','',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
				}

			}

			echo $move."<img src='/BSOS/images/email/L.png' valign=top border=0><img src='$Icon' valign=middle border=0>".$DispData."</font><br/>";
			if($checkrows>0)
				div_addr_Tree_Admin($Data[0],$move."&nbsp;&nbsp;&nbsp;&nbsp;",$curSno,$module);

		}//while
	}


	//to get the edit company   divisions tree  in Admin/Datamanagemnet/Comapnies
	function div_editTree_Admin($par,$move,$curSno,$module)
	{
		global $maildb,$db,$username;
		$Sql="select sno,address1,address2,city,state,country,zip,IF(accessto='ALL','Public',IF(accessto='$username','Private',IF(FIND_IN_SET('$username',accessto)>0,'Share','None'))),status,cname from staffoppr_cinfo where  parent='".$par."' order by cname";
		$Res=mysql_query($Sql,$db);
		$Tot_Cols=mysql_num_fields($Res);
		while($Data=mysql_fetch_row($Res))
		{
			$CheckChild_sql="select sno from staffoppr_cinfo where parent=$Data[0]";
			$CheckChild_res=mysql_query($CheckChild_sql,$db);
			$checkrows=mysql_num_rows($CheckChild_res);
			if($checkrows>0)
				$Icon='/BSOS/images/crm/icon-div.gif';
			else
			$Icon='/BSOS/images/crm/icon-branch.gif';
			$divAddr='';
			$collen=2;
			$divAddr=getCompDivisionAddress($Data[9],$Data[1],$Data[2],$Data[3],$Data[4],getCountry($Data[5]),$Data[6]);
			if($Data[0]==$curSno)
				{
					$put_var='&nbsp;&nbsp;&nbsp;&nbsp;';
					$DispData="&nbsp;<font  class='crmsummary-contentbolddata1'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
			 	}
			 else
			  {
				   $put_var = "<a class=remind-delete-align href=javascript:del_editdivs('$Data[0]','$curSno')><img src='/BSOS/images/crm/icon-delete.gif' width=10 height=9 alt='' border=0 align=left></a>";

					if($Data[8]=='INACTIVE')
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Admin/Data_Mngmt/Companies/hisreviewmanage.php?addr=$Data[0]&module=$module','compArchieve',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
					else
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Admin/Data_Mngmt/Companies/viewcompanySummary.php?addr=$Data[0]&module=$module','',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}

			}

			echo $put_var.$move."<img src='/BSOS/images/email/L.png' valign=top border=0><img src='$Icon' valign=middle border=0>".$DispData."</font><br/>";
			if($checkrows>0)
				div_editTree_Admin($Data[0],$move."&nbsp;&nbsp;&nbsp;&nbsp;",$curSno);

		}//while
	}

	//to get the divisions in Job Order Admin..new
	function JobOrderDivTreeAdmin($par,$move,$curSno,$source_Page,$module)
	{
		global $maildb,$db,$username;

		$Sql="select sno,address1,address2,city,state,country,zip,IF(accessto='ALL','Public',IF (accessto = 'all' or accessto = '' or accessto is null , 'Public',IF (locate(',',accessto)>0,'Share','Private')),status,cname from staffoppr_cinfo where  parent='".$par."' order by cname";
		$Res=mysql_query($Sql,$db);
		$Tot_Cols=mysql_num_fields($Res);
		while($Data=mysql_fetch_row($Res))
		{
			$CheckChild_sql="select sno from staffoppr_cinfo where parent=$Data[0]";
			$CheckChild_res=mysql_query($CheckChild_sql,$db);
			$checkrows=mysql_num_rows($CheckChild_res);
			if($checkrows>0)
				$Icon='/BSOS/images/crm/icon-div.gif';
			else
			$Icon='/BSOS/images/crm/icon-branch.gif';
			$divAddr='';
			$divAddr=getCompDivisionAddress($Data[9],$Data[1],$Data[2],$Data[3],$Data[4],getCountry($Data[5]),$Data[6]);

			if($Data[0]==$curSno)
				{
					$put_var='&nbsp;&nbsp;&nbsp;&nbsp;';
					$DispData="&nbsp;<font  class='crmsummary-contentbolddata1'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
			 	}
			 else
			  {
				   $put_var = "<a class=remind-delete-align href=javascript:del_divs('$Data[0]','$par','$source_Page')><img src='/BSOS/images/crm/icon-delete.gif' width=10 height=9 alt='' border=0 align=left></a>";

				  if($Data[7]!='None')
				    {
					if($Data[8]=='INACTIVE')
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Admin/Data_Mngmt/Companies/hisreviewmanage.php?addr=$Data[0]&module=$module','compArchieve',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
					else
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Admin/Data_Mngmt/Companies/viewcompanySummary.php?addr=$Data[0]&module=$module','',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
				    }
				   else
				   {
						$DispData="&nbsp;<font class='crmsummary-lnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
				   }
			}


			echo $put_var.$move."<img src='/BSOS/images/email/L.png' valign=top border=0><img src='$Icon' valign=middle border=0>".$DispData."</font><br/>";
			if($checkrows>0)
				JobOrderDivTreeAdmin($Data[0],$move."&nbsp;&nbsp;&nbsp;&nbsp;",$curSno,'$source_Page',$module);

		}//while
	}

	//to get the divisions in Job Order Admin..new
	function JobOrder_editTree_admin($par,$move,$curSno,$source_Page,$module)
	{
		global $maildb,$db,$username;
		$Sql="select sno,address1,address2,city,state,country,zip,IF (accessto = 'all' or accessto = '' or accessto is null , 'Public',IF (locate(',',accessto)>0,'Share','Private')),status,cname from staffoppr_cinfo where  parent='".$par."' order by cname";
		$Res=mysql_query($Sql,$db);
		$Tot_Cols=mysql_num_fields($Res);
		while($Data=mysql_fetch_row($Res))
		{
			$CheckChild_sql="select sno from staffoppr_cinfo where parent=$Data[0]";
			$CheckChild_res=mysql_query($CheckChild_sql,$db);
			$checkrows=mysql_num_rows($CheckChild_res);
			if($checkrows>0)

				$Icon='/BSOS/images/crm/icon-div.gif';
			else
			$Icon='/BSOS/images/crm/icon-branch.gif';
			$divAddr='';
			$divAddr=getCompDivisionAddress($Data[9],$Data[1],$Data[2],$Data[3],$Data[4],getCountry($Data[5]),$Data[6]);
			if($Data[0]==$curSno)
				{
					$put_var='&nbsp;&nbsp;&nbsp;&nbsp;';
					$DispData="&nbsp;<font  class='crmsummary-contentbolddata1'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
			 	}
			 else
			  {
				   $put_var = "<a class=remind-delete-align href=javascript:del_editdivs('$Data[0]','$curSno','$source_Page')><img src='/BSOS/images/crm/icon-delete.gif' width=10 height=9 alt='' border=0 align=left></a>";

				  if($Data[7]!='None')
				    {
					if($Data[8]=='INACTIVE')
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Admin/Data_Mngmt/Companies/hisreviewmanage.php?addr=$Data[0]&module=$module','compArchieve',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
					else
					{
						$DispData="&nbsp;<a href=# onclick=openChild('/BSOS/Admin/Data_Mngmt/Companies/viewcompanySummary.php?addr=$Data[0]&module=$module','',1000,700)  class='crmsummary-contentlnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</a>";
					}
				    }
				   else
				   {
						$DispData="&nbsp;<font class='crmsummary-lnk'>".html_tls_specialchars($divAddr,ENT_QUOTES)."</font>";
				   }
			}


			echo $put_var.$move."<img src='/BSOS/images/email/L.png' valign=top border=0><img src='$Icon' valign=middle border=0>".$DispData."</font><br/>";
			if($checkrows>0)
				JobOrder_editTree_admin($Data[0],$move."&nbsp;&nbsp;&nbsp;&nbsp;",$curSno,$source_Page);

		}//while
	}

	//Function Modified columns(time and modified user) updation in emp_list table
	function EmpModifiedUpdate($colName,$recno)
	{
		global $maildb,$db,$username;

		$que="update emp_list set mtime=now() , muser='".$username."' where ".$colName." = '".$recno."'";
		mysql_query($que,$db);
	}

	//function to convert special characters--used while updating the grid without refreshing in CRM and Admin->DataManagement
	function convertSpecCharsGlobal($input)
	{
		//Replacing special characters like ’,” with single quote
		$input=str_replace("’","'",$input);
		$input=str_replace("‘","'",$input);
		$input=str_replace('”',"'",$input);
		$input=str_replace('“',"'",$input);
		$input=str_replace("–","-",$input);

		//--------END----------------------
		$input=preg_replace("/(\n|\t|\r|\b)*/","",$input);
		return html_tls_specialchars(trim($input),ENT_QUOTES);
	}

	//function to convert special characters--used while updating the grid without refreshing in CRM--Chanikya
	function convertSpecCharsCRM($input)
	{
		//Replacing special characters like ’,”,/n,/r,/t,/b with single quote
		$input=str_replace("’","'",$input);
		$input=str_replace("‘","'",$input);
		$input=str_replace('”',"'",$input);
		$input=str_replace('“',"'",$input);
		$input=str_replace("–","-",$input);
		$input=str_replace("\n"," ",$input);
		$input=str_replace("\t"," ",$input);
		$input=str_replace("\r"," ",$input);
		$input=str_replace("\b"," ",$input);

		//--------END----------------------
		$input=preg_replace("/(\n|\t|\r|\b)*/","",$input);
		return html_tls_specialchars(trim($input),ENT_QUOTES);
	}

	//Function for replacing spl chars like *, /, \ with null in the file name.
	function convertSplCharsFileName($fileName)
	{
        $patterns[0] = '/\*/';
        $patterns[1] = '/</';
        $patterns[2] = '/>/';
        $patterns[3] = '/\//';
        $patterns[4] = '/\\\\/';
        $patterns[5] = '/\?/';
        $patterns[6] = '/:/';
        $patterns[7] = '/"/';
        $fileNameConverted = preg_replace($patterns, '', $fileName);

        return $fileNameConverted;
    }

	//function for Deactivate API user account, using in closing placement and HRM Applicant Tracking Fwd to hiring management
	function DeactivateApiUser($uname)
	{
		global $maildb,$db,$username;

		$apiconQry = "select apconlist.username from consultant_list conlist , api_consultant_list apconlist where conlist.username='".$uname."' and conlist.serial_no = apconlist.consultant_id";
		$apiconRes = mysql_query($apiconQry,$db);
		$apiconnumrows = mysql_num_rows($apiconRes);
		if($apiconnumrows > 0)
		{
			$apiconrow = mysql_fetch_row($apiconRes);

			$Update_users = "update users set status = 'DA' , um_deactive_date = NOW(), um_deactive_user ='".$username."' where type='cand' and username = '".$apiconrow[0]."'";
			mysql_query($Update_users,$db);
		}
	}

	//Function to update the consultant applied jobs when candidate is submitted
	function updConAppliedJobStatus($candidateSno,$reqid,$status)
	{
		global $maildb,$db;
		//Query to get the consultant id based on the candidate sno
		$consult_id_que="SELECT serial_no FROM consultant_list,candidate_list WHERE candidate_list.sno='".$candidateSno."' AND candidate_list.candid=consultant_list.username";
		$res_consult_id =  mysql_query($consult_id_que,$db);
		$fetch_consult_id = mysql_fetch_row($res_consult_id);

		//Query to update the consultant applied jobs with status submitted
		$upd_consult_applied="UPDATE consultant_appliedjobs SET status='".$status."' WHERE consultant_id='".$fetch_consult_id[0]."' AND req_id='".$reqid."'";
		mysql_query($upd_consult_applied,$db);
	}

	//Function for update modified user and modified date in users table
	function UpdateModifieddate_Users($appuser)
	{
		global $maildb,$db,$username;
		$upUserqry = "update users set um_mdate=now(),um_muser='".$username."' where username='".$appuser."'";
		mysql_query($upUserqry,$db);
	}

	/* ... .. Modifying other Records(if Needed)...Rajkumar M. .. ... */
	function ShuffleIndex($action,$discon,$status,$maxIndex,$disind,$prevIndex,$prevStatus,$db)
	{
		if(($discon == 'Y') && ($maxIndex != $disind) && $action=='add')
		{
			$arthematic = 'ADD';
		}//End of if(add)
		if($action == 'edit')
		{
			if(trim($prevStatus)=='P' && $status!='P')
			{
				$arthematic = 'SUB';
				$disind = $prevIndex;
			}
			if(trim($prevStatus)!='P' && $status=='P' && ($disind != $maxIndex))
			{
				$arthematic = 'ADD';
			}
			if(trim($prevStatus)=='P' && $status=='P' && ($disind != $prevIndex))
			{
				if($disind < $prevIndex)
				{
					$arthematic = 'ADD';
					$subqry = " and disind <= '$prevIndex' ";
				}
				else
				{
					$arthematic = 'SUB';
					$subqry = " and disind <= '$disind' ";
					$disind = $prevIndex;
				}//End of if-else{}
			}
		}//End of if(edit)

		/* ... .. Checking for condition to continue or to return .. ... */
		if(!$arthematic)
		{
			return false;
		}
		$sql = "select serial_no,disind from contact_manage where disind >= '".$disind."'".$subqry."and status='P' order by disind";
		$rec = mysql_query($sql,$db);
		while($res = mysql_fetch_row($rec))
		{
			$query="update contact_manage set disind='".(($arthematic=='ADD') ? ($res[1]+1) : ($res[1]-1))."' where serial_no='".$res[0]."'";
			mysql_query($query,$db);
		}//End of While{}
 	}//End of Func(Shuffling)
	//Function to get the username for the user 'Appliacnt' ,which is stored as approveuser ,when a candidate is created from API
	function getApplicantUsername()
	{
		global $maildb,$db;
		$sqlApplicantUser="SELECT username FROM users WHERE type='cand' AND userid='Applicant' AND status='DA'";
		$resApplicantUser=mysql_query($sqlApplicantUser,$db);
		$resultAppl=mysql_fetch_row($resApplicantUser);
		$applicantUsername=$resultAppl[0];
		return $applicantUsername;
	}

	//Common Function for checking the domain for displaying the activities.
    function checkDomainForActivities($recordIdVal,$activitiesModuleName)
    {
        global $maildb,$db,$username;
        //variable to check candidate with same domain or not.
        $checkDomainCond_activities = "";

        //Query to fetch the emailid's for Candidates.
        if($activitiesModuleName=="CRM->Cand")
        {
            $actMailQue = "select email from candidate_general where username='".$recordIdVal."' and email!=''";
        }
        else if($activitiesModuleName=="HRM->EM")  //Query to fetch the emailid's for Employees.
        {
            $actMailQue = "select email from hrcon_general where hrcon_general.ustatus = 'active' AND username='".$recordIdVal."' and email!=''";
        }
        else if($activitiesModuleName=="HRM->AT" || $activitiesModuleName=="HRM->OR")  //Query to fetch the emailid's for Applicant or Recruiter.
        {
            $actMailQue = "select email from consultant_general where username='".$recordIdVal."' and email!=''";
        }
        else if($activitiesModuleName=="CRM->Cont") //Query to fetch the emailid's for Contacts.
        {
            $actMailQue = "select email from staffoppr_contact where sno='".$recordIdVal."' and email!=''";
        }
        $actMailRes = mysql_query($actMailQue,$db);
        $totCandCount=mysql_num_rows($actMailRes);

        if($totCandCount>0)
        {
            $actMailRow = mysql_fetch_row($actMailRes);
            $candidateEmail = explode(",",$actMailRow[0]);
            $cand_cnt=count($candidateEmail);
            //For Multiplt Emailid's for the Candidate, checking the Same Domain.
            for($c=0;$c<$cand_cnt;$c++)
            {
                //For multiple Emailid's, If any of the email is of same domain then no need to check for other Emailid's
                if($checkDomainCond_activities=="")
                {
                    //To check the domain name of the emailid and according setting the condition to display the activities.
                    $getCandStat=getContactDomain($candidateEmail[$c]);

                    //If the candidate is of the same domain, then it will return username.
                    if($getCandStat!="ALL")
                    {
                        //$checkDomainCond_activities = " and cmngmt_pr.username='".$username."' "; //checking for username condition for displaying activities.
						$checkDomainCond_activities = " and if(title in ('Email','REmail'),cmngmt_pr.username='".$username."',1)"; //checking for username condition for displaying activities.
                        break;
                    }
                }
            }
        }
        return $checkDomainCond_activities;
    }
	//Function to get Corp Code
	function getCorpCode($codeid,$idtype,$dbcon='')
	{
		global $rptdb ,$db;
		if(trim($idtype)=="client") //If $codeid is the staffacc_cinfo.sno (ie hrcon_jobs.client or empcon_jobs.client or placements_jobs.client)
		{
			$sqlCorpCode="select corp_code.name  from corp_code ,staffacc_cinfo  where staffacc_cinfo.sno='".$codeid."'  and staffacc_cinfo.corp_code=corp_code.sno";
		}
		else if(trim($idtype)=="corpcode") //If $codeid is the staffacc_cinfo.corp_code
		{
			$sqlCorpCode="select name  from corp_code  where sno='".$codeid."' ";
		}
                if($dbcon == '') {
                    $resCorpCode=mysql_query($sqlCorpCode,$rptdb);
                }else {
                    $resCorpCode=mysql_query($sqlCorpCode,$db);
                }
		$corpName=mysql_fetch_row($resCorpCode);
		return  $corpName[0];
	}

	//Function for Getting date range for approved single and multiple timsheets for invoice customer
	function TimesheetDaysRange($Ass_id,$Par_id,$TS_Status)
	{
		global $maildb,$db;

		$TSQry = "select IF(par_timesheet.ts_multiple='Y' or (timesheet.sdate!='0000-00-00' and timesheet.edate!='0000-00-00'),(DATEDIFF(par_timesheet.edate,par_timesheet.sdate)+1),count(timesheet.parid)) from timesheet LEFT JOIN par_timesheet on (par_timesheet.sno=timesheet.parid) where timesheet.client!='' and timesheet.billable!='' and timesheet.status='".$TS_Status."' and timesheet.assid ='".$Ass_id."' and timesheet.parid='".$Par_id."' group by timesheet.parid,timesheet.assid";

		$TSres = mysql_query($TSQry,$db);
		$TS_Row = mysql_fetch_row($TSres);
		$DaysRange = $TS_Row[0];

		return $DaysRange;
	}

	//Function for Getting Day for Hours (or) Days (or) Weeks (or) Months (or) Years....
	function calculateAmountTotal($amount,$currentTime,$requiredTime)
	{
		$value=$amount;
		$currentTime = strtolower($currentTime);
		$requiredTime = strtolower($requiredTime);
		$destvalue = 0;

		//first convert to base value per hour...
		if($currentTime=="hour") { $value=$amount; } elseif($currentTime=="day") { $value=$amount/8; } elseif($currentTime=="week") { $value=$amount/(8*7); } elseif($currentTime=="month") { $value=$amount/(8*30); } elseif($currentTime=="year") { $value=$amount/(8*365); }

		//start of required final value calculation...
		if($requiredTime=="hour") { $destvalue=$value; } elseif($requiredTime=="day") { $destvalue=$value * (8); } elseif($requiredTime=="week") { $destvalue=$value * (8*7); } elseif($requiredTime=="month") { $destvalue=$value * (8*30); } elseif($requiredTime=="year") { $destvalue=$value * (8*365); }

		return number_format($destvalue,2,'.','');
	}

	// Removing the junk character from string -vipin 14/11/2008
	function junkCharacter($input)
	{
			$strPattern = "/[^A-Za-z 0-9 \.,\?\'\"!@#\$%&\*\(\)-_=\+;:<>\/\\\}\{\[\]~]*/";
			$strReplace = "";
			$result=preg_replace($strPattern,$strReplace,$input);
			$result=str_replace("^",$strReplace,$result);
			$result=str_replace("|",$strReplace,$result);
			return $result;
	}
// To convert file name as command prompt acceptable file name.
	function getUnixFileName($filename_name)
	{
		$arrSplReplace = array(' ',',','\"','\'','`','!','@','$','^','&','(',')','=','{','}','[',']',';');
		$arrSplCount = count($arrSplReplace);
		for($varSpl=0; $varSpl<=$arrSplCount;$varSpl++)
		{
			$filename_name=str_replace($arrSplReplace[$varSpl],"\\\\".$arrSplReplace[$varSpl],$filename_name);
		}
		return $filename_name;
	}

	//Function to Convert Billing contact and Billing address of the Accouting Customer into CRM
	function billContactAddress($Crm_Comp_Sno)
	{
		global $username,$maildb,$db;
		$CRM_Cont_SNo_SDataUpdate_Array=array();

		$sel_qry="select bill_contact,bill_address from staffacc_cinfo where username='".$Crm_Comp_Sno."' and crm_comp!='0'";
		$rel_qry=mysql_query($sel_qry,$db);
		$fth_qry=mysql_fetch_row($rel_qry);

		if($fth_qry[1]!="0" && $fth_qry[1]!="")
		{

		$sel_bill="select sno from staffoppr_cinfo where acc_comp='".$fth_qry[1]."'";
		$rel_bill=mysql_query($sel_bill,$db);
		$num_rows=mysql_num_rows($rel_bill);
		$fth_bill=mysql_fetch_row($rel_bill);

		if($num_rows == '0')
		{
			$qry_cinfo="insert into staffoppr_cinfo  (sno,approveuser,cdate,owner,mdate,muser,ceo_president,cfo,sales_purchse_manager,cname,curl,address1,address2,city,state ,country,zip,ctype,csize,nloction,nbyears,nemployee,com_revenue,federalid,bill_req, service_terms,accessto,acc_comp,compowner,compbrief,compsummary,compstatus,dress_code,tele_policy,smoke_policy,parking, park_rate,directions,culture,phone,fax,industry,keytech,department,siccode,csource,ticker,alternative_id,customerid,phone_extn)
			select  '' ,'".$username."',Now(),'".$username."',NOW(),'".$username."', ceo_president , cfo , sales_purchse_manager , cname , curl , address1 , address2 , city , state , country , zip , ctype , csize , nloction , nbyears , nemployee , com_revenue , federalid,bill_req , service_terms,'ALL',sno,compowner,compbrief, compsummary,compstatus,dress_code,tele_policy,smoke_policy,parking, park_rate,directions,culture,phone,fax,industry,keytech, department,siccode,csource,ticker,alternative_id,sno,phone_extn from staffacc_cinfo where sno='".$fth_qry[1]."'";
			mysql_query($qry_cinfo,$db);
			$qry_cinfo_id=mysql_insert_id($db);
		}
		else
			$qry_cinfo_id=$fth_bill[0];

		$upd_opprcon="update staffacc_cinfo set crm_comp='".$qry_cinfo_id."', muser='".$username."', mdate=NOW() where sno='".$fth_qry[1]."'";
		mysql_query($upd_opprcon,$db);
	}
	if($fth_qry[0]!="0" && $fth_qry[0]!="")
	{
		//Query to select the source ,reportto and spouse from staffoppr_contact table
		$oppr_qry="SELECT source,spouse,reportto,crm_cont FROM staffacc_contact WHERE sno='".$fth_qry[0]."'";
		$oppr_exe_qry=mysql_query($oppr_qry,$db);
		$oppr_fch_qry=mysql_fetch_row($oppr_exe_qry);

	if($oppr_fch_qry[3]=='0')
	{
		if($oppr_fch_qry[1]!="" && $oppr_fch_qry[1]!="0")
		{
			$spouse_id = insDumpCrmSpouseSourceReport($oppr_fch_qry[1]); //for inserting spouse details.
			$contid=$spouse_id;
		}
		if($oppr_fch_qry[0]!="" && $oppr_fch_qry[0]!="0")
		{
			$source_id = insDumpCrmSpouseSourceReport($oppr_fch_qry[0]); //for inserting source details.
			$contid=$source_id;
		}

		if($oppr_fch_qry[2]!="" && $oppr_fch_qry[2]!="0")
		{
			$report_id = insDumpCrmSpouseSourceReport($oppr_fch_qry[2]); //for inserting reportsto details
			$contid=$report_id;
		}

		$query3="insert into staffoppr_contact(sno,approveuser,stime,owner,mdate,muser,fname,mname,lname,email,wphone, hphone,mobile,fax,other,ytitle,acc_cont,accessto,csno,cat_id,department,certifications,codes,keywords,messengerid,address1, address2,city,state,country,zipcode,sourcetype,source,reportto,spouse,suffix,prefix,ctype,wphone_extn,hphone_extn,other_extn) select  '' ,'".$username."',Now(),'".$username."',NOW(),'".$username."' ,fname , mname , lname , email , wphone , hphone , mobile , fax , other , ytitle,sno,'','".$qry_cinfo_id."',cat_id,department,certifications, codes,keywords,messengerid,address1,address2,city,state,country,zipcode,sourcetype,'".$source."','".$reportto."','".$spouse."',suffix,prefix,ctype,wphone_extn,hphone_extn,other_extn from staffacc_contact where sno='".$fth_qry[0]."'";
		mysql_query($query3,$db);
		$qry_acccon_id=mysql_insert_id($db);

		$upd_opprcon="update staffacc_contact set crm_cont='".$qry_acccon_id."' where sno='".$fth_qry[0]."'";
		mysql_query($upd_opprcon,$db);
		array_push($CRM_Cont_SNo_SDataUpdate_Array,$qry_acccon_id);

	}
	else
	{
		//Query to select the source ,reportto and spouse from staffacc_contact table
		$sel_su_rep_sp="SELECT source,spouse,reportto FROM staffacc_contact WHERE sno='".$oppr_fch_qry[3]."' and staffacc_contact.username!='' and crm_cont!='0'";
		$res_su_rep_sp=mysql_query($sel_su_rep_sp,$db);
		$fet_su_rep_sp=mysql_fetch_row($res_su_rep_sp);

		//For Updating the details of the Spouse for that particular contact
		if($fet_su_rep_sp[1]!=0)
		{
			updDumpCrmSpouseSourceReport($fet_su_rep_sp[1],$oppr_fch_qry[1]); //for updating spouse details.
			$spouse_id = $fet_su_rep_sp[1];
			$contid=$spouse_id;
		}
		//For Updating the details of the Source for that particular contact
		if($fet_su_rep_sp[0]!=0)
		{
			updDumpCrmSpouseSourceReport($fet_su_rep_sp[0],$oppr_fch_qry[0]); //for updating source details.
			$source_id = $fet_su_rep_sp[0];
			$contid=$source_id;
		}
		//For Updating the details of the Reports to for that particular contact
		if($fet_su_rep_sp[2]!=0)
		{
			updDumpCrmSpouseSourceReport($fet_su_rep_sp[2],$oppr_fch_qry[2]); //for updating reportstto details.
			$report_id = $fet_su_rep_sp[2];
			$contid=$report_id;
		}

			$qry_acccon="update staffoppr_contact a,staffacc_contact b set a.nickname=b.nickname,a.prefix=b.prefix,a.fname=b.fname,a.mname=b.mname,a.lname=b.lname,a.email=b.email,a.cat_id=b.cat_id,a.wphone=b.wphone,a.hphone=b.hphone,a.mobile=b.mobile,a.fax=b.fax,a.other=b.other,a.ytitle=b.ytitle,a.ctype=b.ctype,a.source='".$source_id."',a.department=b.department,a.certifications=b.certifications,a.codes=b.codes,a.keywords=b.keywords,a.messengerid=b.messengerid,a.spouse='".$spouse_id."',a.suffix=b.suffix,a.address1=b.address1,a.address2=b.address2,a.city=b.city,a.state=b.state,a.country=b.country,a.zipcode=b.zipcode,a.reportto='".$report_id."',a.sourcetype=b.sourcetype,a.muser=b.muser,a.mdate=b.mdate,a.wphone_extn=b.wphone_extn,a.hphone_extn=b.hphone_extn,a.other_extn=b.other_extn where a.sno=b.crm_cont and a.sno='".$fth_qry[0]."' and b.sno='".$oppr_fch_qry[3]."'";
			mysql_query($qry_acccon,$db);
			$qry_acccon_id=$oppr_fch_qry[3];
			array_push($CRM_Cont_SNo_SDataUpdate_Array,$qry_acccon_id);
		}
	}

		$que="update staffoppr_cinfo a,staffacc_cinfo b set a.ceo_president=b.ceo_president,a.cfo=b.cfo,a.sales_purchse_manager=b.sales_purchse_manager,a.cname=b.cname,a.curl=b.curl,a.address1=b.address1,a.address2=b.address2,a.city=b.city,a.state=b.state,a.country=b.country,a.zip=b.zip,a.ctype=b.ctype,a.csize=b.csize,a.nloction=b.nloction,a.nbyears=b.nbyears,a.nemployee=b.nemployee,a.com_revenue=b.com_revenue,a.federalid=b.federalid,a.bill_contact='".$qry_acccon_id."',a.bill_address='".$qry_cinfo_id."',a.bill_req=b.bill_req,a.service_terms=b.service_terms,a.compowner=b.compowner,a.compbrief=b.compbrief,a.compsummary=b.compsummary,a.compstatus=b.compstatus,a.dress_code=b.dress_code,a.tele_policy=b.tele_policy,a.smoke_policy=b.smoke_policy,a.parking=b.parking,a.park_rate=b.park_rate,a.directions=b.directions,a.culture=b.culture,a.phone=b.phone,a.fax=b.fax,a.industry=b.industry,a.keytech=b.keytech,a.department=b.department,a.siccode=b.siccode,a.csource=b.csource,a.ticker=b.ticker,a.alternative_id=b.alternative_id,a.phone_extn = b.phone_extn   where a.sno=b.crm_comp and a.acc_comp=b.sno and b.username='".$Crm_Comp_Sno."'";
		mysql_query($que,$db);
	updatecomp_search($qry_cinfo_id);
	//Updating all contact search details
	updatecont_search_data($CRM_Cont_SNo_SDataUpdate_Array);

		return;
  }

   //Function for updating contacts like source or spouse or reports to into Crm contact
    function updDumpCrmSpouseSourceReport($contSno,$opprSno)
    {
        global $username,$maildb,$db;

        $spouse_upd="update staffoppr_contact a,staffacc_contact b set a.fname=b.fname,a.muser=b.muser,a.mdate=b.mdate where b.sno='".$contSno."' and a.sno='".$opprSno."'";
        mysql_query($spouse_upd,$db);
		return;
    }

	//Function for inserting contacts like source or spouse or reports to into Crm contact
    function insDumpCrmSpouseSourceReport($opprSno)
    {
        global $username,$maildb,$db;

       $spouse_que="insert into staffoppr_contact(fname,mname,lname,createdby,status,cdate,acccontact,owner,muser,mdate) select fname,mname,lname,'".$username."',status,NOW(),'N','".$username."','".$username."',NOW() from staffacc_contact where sno='".$opprSno."'";
        mysql_query($spouse_que,$db);
        $spouse_id=mysql_insert_id($db);
        return $spouse_id;
    }

	//This function is to get pay period, standard hours and number of days from cpaysetup table with pipe(|) concatenated.
	function cPaySetupTableFunc($rtnType = 'hours')
	{
		global $maildb,$db;

		$cPaySel = "SELECT payperiod,stdhours,paydays FROM cpaysetup WHERE status='ACTIVE'";
		$cPayRes = mysql_query($cPaySel,$db);
		$cPayRow = mysql_fetch_row($cPayRes);

		if($rtnType == 'hours')
			return $workHours = number_format(number_format($cPayRow[1],2,'.','')/number_format($cPayRow[2],2,'.',''),2,'.','');
		else
			return $cPayRow[0]."|".$cPayRow[1]."|".$cPayRow[2];
	}

	//This function to change Years/Months/Weeks/Days to Hours
	function chngToHours($getPeriod,$getSalaryOrRate,$numHoursPerDay)
	{
		if( $getPeriod == "YEAR" )
			$getConvHours = $getSalaryOrRate / ( $numHoursPerDay * 261 );
		else if( $getPeriod == "MONTH" )
			$getConvHours = $getSalaryOrRate / ( $numHoursPerDay * ( 261 / 12 ) );
		else if( $getPeriod == "WEEK" )
			$getConvHours = $getSalaryOrRate / ( $numHoursPerDay * 5);
		else if( $getPeriod == "DAY" )
			$getConvHours = $getSalaryOrRate / $numHoursPerDay;
		else
			$getConvHours = $getSalaryOrRate;

		return $getConvHours;
	}

	//This function is to get Earnings in hours for Regular, Over Time  & Double Time.
	function chkRegularOverDoubleEarnings($pageName,$parEmpUsername,$fromDate,$toDate,$timesheetPayRoll,$earningStatement,$compenSno,$jobsSno,$cpaysetupSno)
	{
		global $maildb,$db;

		if($pageName == 'emphistory' || $pageName == 'payhistory' || $timesheetPayRoll != '')
		{
			$payRoll = "timesheet.payroll='".$timesheetPayRoll."'";
		}
		else
		{
			$payRoll = "( timesheet.payroll='' OR ISNULL(timesheet.payroll) )";
		}

		$timesheetQue="SELECT SUM(timesheet.thours), assid, SUM(timesheet.othours), SUM( SUBSTRING( timesheet.thours, 1, (INSTR( timesheet.thours, '.' ) -1 ) )), SUM( SUBSTRING( timesheet.thours, (INSTR( timesheet.thours, '.' ) +1 ) ) ), SUM( SUBSTRING( timesheet.othours, 1, (INSTR( timesheet.othours, '.' ) -1 ) )), SUM( SUBSTRING( timesheet.othours, (INSTR( timesheet.othours, '.' ) +1 ) ) ), SUM(timesheet.double_hours), SUM( SUBSTRING( timesheet.double_hours, 1, (INSTR( timesheet.double_hours, '.' ) -1 ) )), SUM( SUBSTRING( timesheet.double_hours, (INSTR( timesheet.double_hours, '.' ) +1 ) ) ) FROM timesheet WHERE ".$payRoll." AND timesheet.username='".$parEmpUsername."' AND timesheet.status IN ('Approved','Billed') AND timesheet.sdate>='".$fromDate."' AND timesheet.sdate<='".$toDate."' AND timesheet.edate='0000-00-00' GROUP BY timesheet.assid";
		$timesheetRes=mysql_query($timesheetQue,$db);
		$timesheetCount = mysql_num_rows($timesheetRes);

		//Checking for Ran Gross pay or Generate Pay Stub
		if($compenSno != "")
		{
			$hrconCompenCond = "sno = '".$compenSno."'";
		}
		else
		{
			$hrconCompenCond = "username = '".$parEmpUsername."' AND ustatus = 'active'";
		}

		//getting pay_assign, assign_overtime, assign_double form hrcon_compen for the particular user
		$payCompenSel = "SELECT pay_assign, assign_overtime, assign_double, salary, salper, over_time, ot_period, double_rate_amt, double_rate_period, timesheet, shper, posworkhr, sno FROM hrcon_compen WHERE ".$hrconCompenCond."";
		$payCompenRes = mysql_query($payCompenSel,$db);
		$payCompenRow = mysql_fetch_row($payCompenRes);

		if($cpaysetupSno != "")
		{
			//Getting cpaysetup table values using serial number
			$cPaySel = "SELECT payperiod,stdhours,paydays FROM cpaysetup WHERE sno='".$cpaysetupSno."'";
			$cPayRes = mysql_query($cPaySel,$db);
			$paytype = mysql_fetch_row($cPayRes);
		}
		else
		{
			//Getting cpaysetup table values
			$getCPaySetup = cPaySetupTableFunc();
			$paytype = explode("|",$getCPaySetup);
		}

		$payPeriod = $paytype[0]; // getting payperiod from cpaysetup table
		$standardHours = $paytype[1]; // getting stdhours from cpaysetup table
		$numberOfDays = $paytype[2]; // getting paydays from cpaysetup table
		$getVal = $standardHours / $numberOfDays; // getting stdhours by paydays

		//Declaring Variable
		$totalHours = "";

		if($payCompenRow[9] == "N" || $payCompenRow[9] == "Y" || $payCompenRow[9] == "") // Temporarly commenting the code for no timesheet functionality
		{
			if($timesheetCount > 0)
			{
				while($timesheetRow=mysql_fetch_array($timesheetRes))
				{
					//Declaring Variables
					$perRegularHour = "";
					$perOverTimeHour = "";
					$perDoubleTimeHour = "";
					$currentRateNow = "";
					$regularRate = "";
					$overtimeRate = "";
					$doubletimeRate = "";

					//Checking for Ran Gross pay or Generate Pay Stub
					if($jobsSno != "")
					{
						$hrconJobsCond = "AND hrcon_jobs.pusername='".$timesheetRow[1]."' AND  hrcon_jobs.sno IN (".$jobsSno.")";
					}
					else
					{
						$hrconJobsCond = "AND hrcon_jobs.username ='".$parEmpUsername."' AND hrcon_jobs.pusername = '".$timesheetRow[1]."'";
					}

					//getting rate, pamount, rateperiod, pperiod, otrate, otprate_amt, ot_period, otprate_period, double_prate_amt, double_prate_period form hrcon_jobs for the particular user
					$payJobSel = "SELECT if((manage.name='Internal Direct' OR manage.name='Direct'),hrcon_jobs.rate,hrcon_jobs.pamount), if((manage.name='Internal Direct' OR manage.name='Direct'),hrcon_jobs.rateperiod,hrcon_jobs.pperiod),if((manage.name='Internal Direct' OR manage.name='Direct'),hrcon_jobs.otrate,hrcon_jobs.otprate_amt),if((manage.name='Internal Direct' OR manage.name='Direct'),hrcon_jobs.ot_period,hrcon_jobs.otprate_period),if((manage.name='Internal Direct' OR manage.name='Direct'),hrcon_jobs.double_prate_amt,hrcon_jobs.double_prate_amt),if((manage.name='Internal Direct' OR manage.name='Direct'),hrcon_jobs.double_prate_period,hrcon_jobs.double_prate_period) FROM hrcon_jobs LEFT JOIN manage ON (hrcon_jobs.jotype=manage.sno AND manage.type='jotype') WHERE hrcon_jobs.ustatus IN ('active', 'closed','cancel') ".$hrconJobsCond." GROUP BY hrcon_jobs.username";
					$payJobRes = mysql_query($payJobSel,$db);
					$payJobRow = mysql_fetch_row($payJobRes);

					if($payCompenRow[0] == "Y") // if pay_assign equal to Y
					{
						$perRegularHour = chngToHours($payJobRow[1],$payJobRow[0],$getVal); // getting regular rate in hours
					}
					else // if pay_assign equal to N
					{
						$perRegularHour = chngToHours($payCompenRow[4],$payCompenRow[3],$getVal); // getting regular rate in hours
					}

					if($payCompenRow[1] == "Y") // if assign_overtime equal to Y
					{
						$perOverTimeHour = chngToHours($payJobRow[3],$payJobRow[2],$getVal); // getting over time rate in hours
					}
					else // if assign_overtime equal to N
					{
						$perOverTimeHour = chngToHours($payCompenRow[6],$payCompenRow[5],$getVal); // getting over time rate in hours
					}

					if($payCompenRow[2] == "Y") // if assign_double equal to Y
					{
						$perDoubleTimeHour = chngToHours($payJobRow[5],$payJobRow[4],$getVal); // getting double time rate in hours
					}
					else // if assign_double equal to N
					{
						$perDoubleTimeHour = chngToHours($payCompenRow[8],$payCompenRow[7],$getVal); // getting double time rate in hours
					}

					$regularRate = $timesheetRow[0] * $perRegularHour; //getting total regular rate

					$overtimeRate = $timesheetRow[2] * $perOverTimeHour; //getting total over time rate

					$doubletimeRate = $timesheetRow[7] * $perDoubleTimeHour; //getting total double time rate

					$currentRateNow = $regularRate+$overtimeRate+$doubletimeRate; // adding regular, over time & double time rates


					$totalHours = round($totalHours+$currentRateNow,2); // adding total rates nothing but earning
					//echo $earningStatement;
					if($earningStatement != '')
					{
						$earningStatementDesign .= "<tr>
							<td align=right><font class=afontstyle>".$timesheetRow[1]."</font></td>
							<td align=right><font class=afontstyle>".$timesheetRow[0]."</font></td>
							<td align=right><font class=afontstyle>";

						if($payJobRow[1]=="FLATFEE")
							$earningStatementDesign .= "FLATFEE";
						else
							$earningStatementDesign .= number_format($perRegularHour,2,".","");

						$earningStatementDesign .= "</font></td>
							<td align=right><font class=afontstyle>".$timesheetRow[2]."</font></td>
							<td align=right><font class=afontstyle>";

						if($payJobRow[3]=="FLATFEE")
							$earningStatementDesign .= "FLATFEE";
						else
							$earningStatementDesign .= number_format($perOverTimeHour,2,".","");

						$earningStatementDesign .= "</font></td>
							<td align=right><font class=afontstyle>".$timesheetRow[7]."</font></td>
							<td align=right><font class=afontstyle>";

						if($payJobRow[5]=="FLATFEE")
							$earningStatementDesign .= "FLATFEE";
						else
							$earningStatementDesign .= number_format($perDoubleTimeHour,2,".","");

						$earningStatementDesign .= "</font></td>
							<td align=right><font class=afontstyle>".number_format($currentRateNow,2,".","")."</font></td>
						</tr>";

					}
				}
				if($earningStatement != '')
				{
					$earningStatementDesign .= "<tr>
    						<td colspan=8><hr></td>
    					</tr>
						<tr>
							<td align=left colspan=2><font class=afontstyle><b>Gross Pay</b></font></td>
							<td><font class=afontstyle>&nbsp;</font></td>
							<td align=right><font class=afontstyle>&nbsp;</font></td>
							<td align=right><font class=afontstyle>&nbsp;</font></td>
							<td align=right><font class=afontstyle>&nbsp;</font></td>
							<td align=right colspan='2'><font class=afontstyle>$ ".number_format(($totalHours),2,".","")."</font></td>
						</tr>
						<input type='hidden' name='earnings' value=".number_format(($totalHours),2,".","").">";
				}
			}

		}
		else //No timesheet caluclation
		{
			$perRegularHour = chngToHours($payCompenRow[4],$payCompenRow[3],$getVal); // getting regular rate in hours
			$perOverTimeHour = chngToHours($payCompenRow[6],$payCompenRow[5],$getVal); // getting over time rate in hours
			$perDoubleTimeHour = chngToHours($payCompenRow[8],$payCompenRow[7],$getVal); // getting double time rate in hours

			$tabQuery = "SELECT appno FROM tabappoint WHERE contactsno LIKE '".$payCompenRow[12]."|%' AND modulename='HR->Compensation' AND recstatus='active'";
			$tabres=mysql_query($tabQuery,$db);
			$tabrow = mysql_fetch_row($tabres);
			$tabappiont_compen_id = $tabrow[0];

			// To get the hours with date related
			$hrconTabQue = "select starthour,endhour from hrcon_tab where tabsno='".$tabappiont_compen_id."' and coltype='compen' and sch_date>='".$fromDate."' and sch_date<='".$toDate."'";
			$hrconTabRes=mysql_query($hrconTabQue,$db);
			$hrconTabCount = mysql_num_rows($hrconTabRes);
			if($hrconTabCount > 0)
			{
				$stdhours = 0;
				while($hrconTabRow=mysql_fetch_array($hrconTabRes))
				{
					$stime1=str_replace(":",".",$hrconTabRow[0]);
					$etime1=str_replace(":",".",$hrconTabRow[1]);

					if($stime1 > $etime1)
						$totalhours = (24 - $stime1) + $etime1;
					else
						$totalhours = $etime1 - $stime1;

					$tothours = explode(".",$totalhours);
					$stime = explode(".",$stime1);
					$etime = explode(".",$etime1);

					$mm1 = $mm2 = 0;

					if($etime[1] > 0)
					{
						$mm1 = $etime[1] * (5 / 3);
					}

					if($stime[1] > 0)
					{
						$mm2 = $stime[1] * (5 / 3);
					}

					$mm = abs($mm1 - $mm2);

					$tothours1 = "$tothours[0].$mm";
					$stdhours += abs($tothours1);
				}
			}
			else
			{
				// To get the hours with out date related
				$hrconTabQry = "select starthour,endhour from hrcon_tab where tabsno='".$tabappiont_compen_id."' and coltype='compen' and sch_date='0000-00-00 00:00:00'";
				$hrconTabResult=mysql_query($hrconTabQry,$db);

				$stdhours = 0;

				while($hrconTabRows=mysql_fetch_array($hrconTabResult))
				{
					$stime1=str_replace(":",".",$hrconTabRows[0]);
					$etime1=str_replace(":",".",$hrconTabRows[1]);

					if($stime1 > $etime1)
							$totalhours = (24 - $stime1) + $etime1;
					else
						$totalhours = $etime1 - $stime1;

					$tothours = explode(".",$totalhours);
					$stime = explode(".",$stime1);
					$etime = explode(".",$etime1);

					$mm1 = $mm2 = 0;
					if($etime[1] > 0)
					{
						$mm1 = $etime[1] * (5 / 3);
					}
					if($stime[1]>0)
					{
						$mm2 = $stime[1] * (5 / 3);
					}

					$mm = abs($mm1 - $mm2);
					$tothours1 = "$tothours[0].$mm";
					$stdhours += abs($tothours1);
				}
			}
			// Hours perday to add one day for .
			$perdayh = number_format(($stdhours / 7),2,'.','');
			if ($payPeriod == 'Weekly')
			{
				$stdhours = $stdhours;
			}
			else if ($payPeriod == 'Bi-weekly')
			{
				$stdhours = $stdhours * 2;
			}
			else if ($payPeriod == 'Semi-Monthly')
			{
				$stdhours = ($stdhours * 2) + $perdayh;
			}
			else if ($payPeriod == 'Monthly')
			{
				$stdhours = (($stdhours * 2) + $perdayh) * 2;
			}

			if($row[3]=="FLATFEE")
				$total = $payCompenRow[3];
			else
				$total = $stdhours * $perRegularHour;

			$project = "Project";
			$currentRateNow = $totalHours = $total;

			if($earningStatement != '')
			{
				$earningStatementDesign = "<tr>
					<td align=right><font class=afontstyle>".$project."</font></td>
					<td align=right><font class=afontstyle>".$stdhours."</font></td>
					<td align=right><font class=afontstyle>";

				if($payJobRow[1]=="FLATFEE")
					$earningStatementDesign .= "FLATFEE";
				else
					$earningStatementDesign .= number_format($perRegularHour,2,".","");

				$earningStatementDesign .= "</font></td>
					<td align=right><font class=afontstyle>0.00</font></td>
					<td align=right><font class=afontstyle>";

				if($payJobRow[3]=="FLATFEE")
					$earningStatementDesign .= "FLATFEE";
				else
					$earningStatementDesign .= number_format($perOverTimeHour,2,".","");

				$earningStatementDesign .= "</font></td>
					<td align=right><font class=afontstyle>0.00</font></td>
					<td align=right><font class=afontstyle>";

				if($payJobRow[5]=="FLATFEE")
					$earningStatementDesign .= "FLATFEE";
				else
					$earningStatementDesign .= number_format($perDoubleTimeHour,2,".","");

				$earningStatementDesign .= "</font></td>
					<td align=right><font class=afontstyle>".number_format($currentRateNow,2,".","")."</font></td>
				</tr>
				<tr>
					<td colspan=8><hr></td>
				</tr>
				<tr>
					<td align=left colspan=2><font class=afontstyle><b>Gross Pay</b></font></td>
					<td><font class=afontstyle>&nbsp;</font></td>
					<td align=right><font class=afontstyle>&nbsp;</font></td>
					<td align=right><font class=afontstyle>&nbsp;</font></td>
					<td align=right><font class=afontstyle>&nbsp;</font></td>
					<td align=right colspan='2'><font class=afontstyle>$ ".number_format(($currentRateNow),2,".","")."</font></td>
				</tr>
				<input type='hidden' name='earnings' value=".number_format(($currentRateNow),2,".","").">";
			}
		}
		if($earningStatement == '')
			return $totalHours; // returning total earnings (or) hours.
		else
			return $earningStatementDesign."|".$totalHours; // returning earning statement design for earnings
	}
/*****************************************************************
	Purpose 		: 	To Identify the Browser,its version and OS of the client
    Soruce url		:	http://apptools.com/phptools/browser/source.php
	Implemented By 	: 	Rajkumar M.
	Usage			: 	$getBrowser = new getBrowserInfo;//creating instance of the class
						$getBrowser->Name;//Calling Properties
						$getBrowser->checkBrowserInfo(browser,version,condition)//Calling method
    **************************************************************/
	class getBrowserInfo{

		var $Name = "Unknown";
		var $Version = "Unknown";
		var $Platform = "Unknown";
		var $UserAgent = "Not reported";
		var $AOL = false;

		function getBrowserInfo(){
			$agent = $_SERVER['HTTP_USER_AGENT'];

			// initialize properties
			$bd['platform'] = "Unknown";
			$bd['browser'] = "Unknown";
			$bd['version'] = "Unknown";
			$this->UserAgent = $agent;

			// find operating system
			if (eregi("win", $agent))
				$bd['platform'] = "Windows";
			elseif (eregi("mac", $agent))
				$bd['platform'] = "MacIntosh";
			elseif (eregi("linux", $agent))
				$bd['platform'] = "Linux";
			elseif (eregi("OS/2", $agent))
				$bd['platform'] = "OS/2";
			elseif (eregi("BeOS", $agent))
				$bd['platform'] = "BeOS";

			// test for Opera
			if (eregi("opera",$agent)){
				$val = stristr($agent, "opera");
				if (eregi("/", $val)){
					$val = explode("/",$val);
					$bd['browser'] = $val[0];
					$val = explode(" ",$val[1]);
					$bd['version'] = $val[0];
				}else{
					$val = explode(" ",stristr($val,"opera"));
					$bd['browser'] = $val[0];
					$bd['version'] = $val[1];
				}

			// test for WebTV
			}elseif(eregi("webtv",$agent)){
				$val = explode("/",stristr($agent,"webtv"));
				$bd['browser'] = $val[0];
				$bd['version'] = $val[1];

			// test for MS Internet Explorer version 1
			}elseif(eregi("microsoft internet explorer", $agent)){
				$bd['browser'] = "MSIE";
				$bd['version'] = "1.0";
				$var = stristr($agent, "/");
				if (ereg("308|425|426|474|0b1", $var)){
					$bd['version'] = "1.5";
				}

			// test for NetPositive
			}elseif(eregi("NetPositive", $agent)){
				$val = explode("/",stristr($agent,"NetPositive"));
				$bd['platform'] = "BeOS";
				$bd['browser'] = $val[0];
				$bd['version'] = $val[1];

			// test for MS Internet Explorer
			}elseif(eregi("msie",$agent) && !eregi("opera",$agent)){
				$val = explode(" ",stristr($agent,"msie"));
				$bd['browser'] = $val[0];
				$bd['version'] = $val[1];

			// test for MS Pocket Internet Explorer
			}elseif(eregi("mspie",$agent) || eregi('pocket', $agent)){
				$val = explode(" ",stristr($agent,"mspie"));
				$bd['browser'] = "MSPIE";
				$bd['platform'] = "WindowsCE";
				if (eregi("mspie", $agent))
					$bd['version'] = $val[1];
				else {
					$val = explode("/",$agent);
					$bd['version'] = $val[1];
				}

			// test for Galeon
			}elseif(eregi("galeon",$agent)){
				$val = explode(" ",stristr($agent,"galeon"));
				$val = explode("/",$val[0]);
				$bd['browser'] = $val[0];
				$bd['version'] = $val[1];

			// test for Konqueror
			}elseif(eregi("Konqueror",$agent)){
				$val = explode(" ",stristr($agent,"Konqueror"));
				$val = explode("/",$val[0]);
				$bd['browser'] = $val[0];
				$bd['version'] = $val[1];

			// test for iCab
			}elseif(eregi("icab",$agent)){
				$val = explode(" ",stristr($agent,"icab"));
				$bd['browser'] = $val[0];
				$bd['version'] = $val[1];

			// test for OmniWeb
			}elseif(eregi("omniweb",$agent)){
				$val = explode("/",stristr($agent,"omniweb"));
				$bd['browser'] = $val[0];
				$bd['version'] = $val[1];

			// test for Phoenix
			}elseif(eregi("Phoenix", $agent)){
				$bd['browser'] = "Phoenix";
				$val = explode("/", stristr($agent,"Phoenix/"));
				$bd['version'] = $val[1];

			// test for Firebird
			}elseif(eregi("firebird", $agent)){
				$bd['browser']="Firebird";
				$val = stristr($agent, "Firebird");
				$val = explode("/",$val);
				$bd['version'] = $val[1];

			// test for Firefox
			}elseif(eregi("Firefox", $agent)){
				$bd['browser']="Firefox";
				$val = stristr($agent, "Firefox");
				$val = explode("/",$val);
				$bd['version'] = $val[1];

		  // test for Mozilla Alpha/Beta Versions
			}elseif(eregi("mozilla",$agent) &&
				eregi("rv:[0-9].[0-9][a-b]",$agent) && !eregi("netscape",$agent)){
				$bd['browser'] = "Mozilla";
				$val = explode(" ",stristr($agent,"rv:"));
				eregi("rv:[0-9].[0-9][a-b]",$agent,$val);
				$bd['version'] = str_replace("rv:","",$val[0]);

			// test for Mozilla Stable Versions
			}elseif(eregi("mozilla",$agent) &&
				eregi("rv:[0-9]\.[0-9]",$agent) && !eregi("netscape",$agent)){
				$bd['browser'] = "Mozilla";
				$val = explode(" ",stristr($agent,"rv:"));
				eregi("rv:[0-9]\.[0-9]\.[0-9]",$agent,$val);
				$bd['version'] = str_replace("rv:","",$val[0]);

			// test for Lynx & Amaya
			}elseif(eregi("libwww", $agent)){
				if (eregi("amaya", $agent)){
					$val = explode("/",stristr($agent,"amaya"));
					$bd['browser'] = "Amaya";
					$val = explode(" ", $val[1]);
					$bd['version'] = $val[0];
				} else {
					$val = explode("/",$agent);
					$bd['browser'] = "Lynx";
					$bd['version'] = $val[1];
				}

			// test for Safari
			}elseif(eregi("safari", $agent)){
				$bd['browser'] = "Safari";
				$bd['version'] = "";

			// remaining two tests are for Netscape
			}elseif(eregi("netscape",$agent)){
				$val = explode(" ",stristr($agent,"netscape"));
				$val = explode("/",$val[0]);
				$bd['browser'] = $val[0];
				$bd['version'] = $val[1];
			}elseif(eregi("mozilla",$agent) && !eregi("rv:[0-9]\.[0-9]\.[0-9]",$agent)){
				$val = explode(" ",stristr($agent,"mozilla"));
				$val = explode("/",$val[0]);
				$bd['browser'] = "Netscape";
				$bd['version'] = $val[1];
			}

			// clean up extraneous garbage that may be in the name
			$bd['browser'] = ereg_replace("[^a-z,A-Z]", "", $bd['browser']);
			// clean up extraneous garbage that may be in the version
			$bd['version'] = ereg_replace("[^0-9,.,a-z,A-Z]", "", $bd['version']);

			// check for AOL
			if (eregi("AOL", $agent)){
				$var = stristr($agent, "AOL");
				$var = explode(" ", $var);
				$bd['aol'] = ereg_replace("[^0-9,.,a-z,A-Z]", "", $var[1]);
			}

			// finally assign our properties
			$this->Name = $bd['browser'];
			$this->Version = $bd['version'];
			$this->Platform = $bd['platform'];
			$this->AOL = $bd['aol'];
		}//End of Constructor(browser)
		function checkBrowserInfo($BId,$ver,$cndtype)
		{
			if($this->Name === $BId)
			{
				if($ver != "")
				{
					switch($cndtype){
						case '>':
							if($ver > $this->Version)
								return true;
							else
								return false;
						break;

						case '<':
							if($ver < $this->Version)
								return true;
							else
								return false;
						break;

						case '<=':
							if($ver <= $this->Version)
								return true;
							else
								return false;
						break;
						case '>=':
							if($ver >= $this->Version)
								return true;
							else
								return false;
						break;

						default:
							if($ver == $this->Version)
								return true;
							else
								return false;
						break;

					}//end of Switch()
				}//End of if(;)
				else
					return true;
			}//End of if();
			else
				return false;
		}//End of the Func(checkBrowserInfo)
	}//End of the class{getBrowserInfo}

function GetTemDir()
{
	global $WDOCUMENT_ROOT,$attach_folder;
	while(is_dir($isDirEx))
	{
		$attach_folder_temp=rand(0,100);
		$isDirEx=$WDOCUMENT_ROOT."/".$attach_folder.$attach_folder_temp;
	}

	$attach_folder=$attach_folder.$attach_folder_temp;
	$isDirEx=$WDOCUMENT_ROOT."/".$attach_folder;
	@mkdir($isDirEx,0777);
	return $isDirEx;
}


	//Common Categories Dropdown list for Collaboration -> TaskManager, Activities -> Tasks.
	function ShowTasks($selvals)
	{
		global $username,$maildb,$db;

		$DisplayTask = "<select name=\"ttype\" id=\"ttype\" size=\"3\" multiple=\"multiple\">";

		if($selvals != "")
			$selvals = explode(",",$selvals);

		$sqry_tasks = "select * from tasks";
		$srs_tasks  = mysql_query($sqry_tasks,$db);
		$i = 0;
		while($srow_tasks = mysql_fetch_row($srs_tasks))
		{
			if($selvals != "" && in_array($srow_tasks[0],$selvals))
				$DisplayTask .= "<option value=\"".$srow_tasks[0]."\" selected>".$srow_tasks[1]."</option>";
			elseif($selvals == "" && $i==0)
				$DisplayTask .= "<option value=\"".$srow_tasks[0]."\" selected>".$srow_tasks[1]."</option>";
			else
				$DisplayTask .= "<option value=\"".$srow_tasks[0]."\">".$srow_tasks[1]."</option>";
			$i++;
		}
		$DisplayTask .= "</select>";
		return $DisplayTask;
	}

	//Common Categories Dropdown list for Collaboration -> Calendar, Activities -> Appointments.
	function ShowAptmt($selvals)
	{
		global $username,$maildb,$db;

		if($selvals=="")
		{
			$meeting_que = "select sno,name from manage where type='appt_category' and name = 'Meeting'";
			$meeting_res = mysql_query($meeting_que,$db);
			$meeting_row = mysql_fetch_row($meeting_res);
			$selvals = array($meeting_row[0]);
		}

		$DisplayAptmt = "<select name=\"type\" id=\"type\" size=\"6\" multiple=\"multiple\" style=\"width:100%;\">";

		$i = 0;
		$sqry_manage = "select sno,name from manage where type='appt_category' order by name";
		$srs_manage  = mysql_query($sqry_manage,$db);
		while($srow_manage = mysql_fetch_row($srs_manage))
		{
			if(in_array($srow_manage[0],$selvals))
				$DisplayAptmt .= "<option value=\"".$srow_manage[0]."\" selected>".$srow_manage[1]."</option>";
			else
				$DisplayAptmt .= "<option value=\"".$srow_manage[0]."\">".$srow_manage[1]."</option>";
			$i++;
		}
		$DisplayAptmt .= "</select>";
		return $DisplayAptmt;
	}

	//This function is to get Earnings in hours for Regular, Over Time  & Double Time.
	function getRegularOverDoubleEarns($pageName,$parEmpUsername,$fromDate,$toDate,$timesheetPayRoll,$earningStatement,$netHoursRate,$jobsSno,$cpaysetupSno)
	{
        global $maildb,$db;
        $earningStatementDesign="";

		if($timesheetPayRoll != '')
		{
			$payRoll = "timesheet_hours.payroll='".$timesheetPayRoll."'";
		}
		else
		{
			$payRoll = "( timesheet_hours.payroll='' OR ISNULL(timesheet_hours.payroll) )";
		}

		if($pageName != 'UpdateNet')
			$cPaySel = "SELECT payperiod,stdhours,paydays FROM cpaysetup WHERE sno='".$cpaysetupSno."'";
		else
			$cPaySel = "SELECT payperiod,stdhours,paydays FROM cpaysetup WHERE  status ='ACTIVE'";

		$cPayRes = mysql_query($cPaySel,$db);
		$paytype = mysql_fetch_row($cPayRes);

		$payPeriod = $paytype[0]; // getting payperiod from cpaysetup table
		$standardHours = $paytype[1]; // getting stdhours from cpaysetup table
		$numberOfDays = $paytype[2]; // getting paydays from cpaysetup table
		$getVal = $standardHours / $numberOfDays; // getting stdhours by paydays

		if($pageName != 'UpdateNet')
		{
        	$netQuery = "SELECT assign_id,amount,hours,type_hours FROM net_hoursrate WHERE payperiod_id='".$netHoursRate."'";
		}
		else
		{
			$netQuery="SELECT timesheet_hours.assid AS AsgnID,'', SUM(timesheet_hours.hours) AS Hours,  mht.name AS HoursType,
 if((mht.rateid = 'rate1' OR mht.rateid = 'rate2' OR mht.rateid = 'rate3') , if(mht.rateid = 'rate1', if(hrcon_compen.pay_assign = 'Y', hrmp.rate, hrcon_compen.salary), if(mht.rateid = 'rate2', if(hrcon_compen.assign_overtime='Y', hrmp.rate, hrcon_compen.over_time), if(mht.rateid = 'rate3', if(hrcon_compen.assign_double='Y', hrmp.rate, hrcon_compen.double_rate_amt), hrmp.rate))),hrmp.rate) AS Rate,
 if((mht.rateid = 'rate1' OR mht.rateid = 'rate2' OR mht.rateid = 'rate3') , if(mht.rateid = 'rate1', if(hrcon_compen.pay_assign = 'Y', hrmp.period, hrcon_compen.salper), if(mht.rateid = 'rate2', if(hrcon_compen.assign_overtime='Y', hrmp.period, hrcon_compen.ot_period), if(mht.rateid = 'rate3', if(hrcon_compen.assign_double='Y', hrmp.period, hrcon_compen.double_rate_period), hrmp.period))),hrmp.period) AS Period, hrcon_jobs.sno,mht.rateid AS RateId
 FROM timesheet_hours LEFT JOIN hrcon_compen ON hrcon_compen.username = timesheet_hours.username
 LEFT JOIN hrcon_jobs ON (hrcon_jobs.pusername = timesheet_hours.assid AND hrcon_jobs.username = timesheet_hours.username)
 LEFT JOIN multiplerates_assignment hrmp ON  hrcon_jobs.sno = hrmp.asgnid AND timesheet_hours.hourstype = hrmp.ratemasterid AND hrmp.ratetype = 'payrate' AND hrmp.asgn_mode = 'hrcon'
  LEFT JOIN multiplerates_master mht ON  timesheet_hours.hourstype = mht.rateid AND mht.rateid = hrmp.ratemasterid AND mht.status = 'ACTIVE'
 WHERE ".$payRoll." AND timesheet_hours.username='".$parEmpUsername."' AND timesheet_hours.status IN ('Approved','Billed')  AND timesheet_hours.sdate>='".$fromDate."' AND timesheet_hours.sdate<='".$toDate."' AND timesheet_hours.edate='0000-00-00'  AND hrcon_compen.ustatus = 'active' AND hrcon_jobs.ustatus IN('active','cancel','closed') AND  timesheet_hours.assid is not null GROUP BY AsgnID, timesheet_hours.hourstype HAVING Hours != 0 order by 1
";
		}

        $netRes=mysql_query($netQuery,$db);
        $netHoursCount = mysql_num_rows($netRes);
        $currentRateNow=0;
        if($netHoursCount)
        {
			$earningStatementDesign .="<tr>
							<td align=left><font class=afontstyle>Assignments</font></td>
							<td align=left><font class=afontstyle>Hours Type</font></td>
							<td align=right><font class=afontstyle>Hours</font></td>
							<td align=right><font class=afontstyle>($)Rate</font></td>
							<td align=right><font class=afontstyle>($)Total Pay</font></td>
						  </tr>
						  <tr>
							<td colspan=5><hr /></td>
						  </tr>";
			$hoursRateValues = "";
			$net_hoursrate = "";
			$hrconjobs_sno = "";
            while($netRow = mysql_fetch_row($netRes))
            {
				if($hrconjobs_sno == '')
					$hrconjobs_sno = $netRow[6];
				else
					$hrconjobs_sno.= ','.$netRow[6];

				if($pageName == 'UpdateNet')
					$netRow[1] = chngToHours($netRow[5],$netRow[4],$getVal);

				$netRow[1] = number_format(($netRow[1]),2,".","");

				if($netRow[5] == 'FLATFEE')
				{
					$stdhours = $netRow[1];
					$netRow[2] = "FLATFEE";
				}
				else if($netRow[2] == "FLATFEE")
				{
					$stdhours = $netRow[1];
				}
				else
					$stdhours = $netRow[2] * $netRow[1];

				$stdhours = number_format(($stdhours),2,".","");

                $earningStatementDesign .= "<tr>
                	<td align=left><font class=afontstyle>".$netRow[0]."</font></td>
                	<td align=left><font class=afontstyle>".$netRow[3]."</font></td>
                	<td align=right><font class=afontstyle>".$netRow[2]."</font></td>
                	<td align=right><font class=afontstyle>".$netRow[1]."</font></td>
                	<td align=right><font class=afontstyle>".number_format(($stdhours),2,".","")."</font></td>";

                $earningStatementDesign .= "</font></td>
                </tr><tr>
                	<td colspan=5></td></tr>";

                $currentRateNow = $currentRateNow+$stdhours;
				if($net_hoursrate == '')
					$net_hoursrate = $netRow[0]."^^".$netRow[1]."^^".$netRow[2]."^^".$netRow[3]."^^".$netRow[7];
				else
					$net_hoursrate .= "||".$netRow[0]."^^".$netRow[1]."^^".$netRow[2]."^^".$netRow[3]."^^".$netRow[7];
            }
        }
        else
        {
            $earningStatementDesign .= "<tr>
        									<td colspan=5 align=center><font class=afontstyle>No Earnings Available.</font></td>
       									</tr>";
        }
		$currentRateNow = number_format(($currentRateNow),2,".","");
        $earningStatementDesign .= "<tr>
        	<td colspan=5><hr></td>
        </tr><tr>
        	<td align=left colspan=2><font class=afontstyle><b>Gross Pay</b></font></td>
       	    <td align=right><font class=afontstyle>&nbsp;</font></td>
        	<td align=right colspan='2'><font class=afontstyle>$ ".number_format(($currentRateNow),2,".","")."</font></td>
        </tr>
        <input type='hidden' name='earnings' value=".number_format(($currentRateNow),2,".","").">";

        if($earningStatement != '')
		{
            return $earningStatementDesign."^|^".$currentRateNow."^|^".$net_hoursrate."^|^".$hrconjobs_sno; // returning earning statement design for earnings
			//Note: '|' is used for assginment Names.
		}
        else
            return $earningStatementDesign;
	}

	//function for multiple selection
	function getSelMulti($a,$b)
	{
		if(strpos("*,$a,",",$b,"))
			return "selected";
		else
			return "";
	}

    //function for multiple display of manage types.
	function getMultiManage($sno)
	{
		global $maildb,$db;

		if($sno)
		{
            $manage_sql="select group_concat(name) from manage where sno in(".$sno.")";
            $manage_res=mysql_query($manage_sql,$db);
            $manage_Data=mysql_fetch_row($manage_res);
		}
		return $manage_Data[0];
	}
	// Function to update Spouse or Reportto or Source value with contact first name, middle name, last name (if contact is updated).
	function updContact($tab,$contSno)
	{
		global $maildb,$db;
		$num=0;
		$num=getContactSno($tab,$contSno,"source");
		if($num>0)
		{
			$fieldset="source_name";
		}
		else
		{
			$num=getContactSno($tab,$contSno,"reportto");
			if($num>0)
			{
					$fieldset="reportto_name";
			}
			else
			{
				$num=getContactSno($tab,$contSno,"spouse");
				if($num>0)
				{
					$fieldset="spouse_name";
				}
			}
		}

		if($num!=0)
		{
			if($tab=="CRM")
				$table="staffoppr_contact";
			else if($tab=="ACC")
				$table="staffacc_contact";

			$upd_Cont="UPDATE $table main, $table fld SET main.$fieldset=replace(concat_ws(' ',trim(fld.fname),trim(fld.mname),trim(fld.lname)),'  ',' ') WHERE fld.sno='".$contSno."' and main.sno='".$num."'";
			mysql_query($upd_Cont,$db);

			if($tab=="CRM")
			{
				$sel_que="select count(1) from staffacc_contact where crm_cont='".$num."'";
				$sel_res=mysql_query($sel_que,$db);
				$sel_num=mysql_fetch_row($sel_res);
				if($sel_num[0]>0)
				{
					$upd_Cont="UPDATE staffacc_contact main, staffoppr_contact fld SET main.$fieldset=fld.$fieldset WHERE main.crm_cont='".$num."' and main.crm_cont=fld.sno";
					mysql_query($upd_Cont,$db);
				}
			}
		}
	}
	//to get sno of oppr_contact table...-prasadd
	function getContactSno($tab,$contSno,$field)
	{
		global $maildb,$db;
		if($tab=="CRM")
			$table="staffoppr_contact";
		else if($tab=="ACC")
			$table="staffacc_contact";

		$que="SELECT sno FROM $table WHERE $field=".$contSno;
		$res=mysql_query($que,$db);
		$row=mysql_fetch_row($res);
		$num=mysql_num_rows($res);
		if($num>0)
			return $row[0];
		else
			return 0;
	}
	//Function to Trim the Single/multiple Re: from the mail subject.- Vijaya
	function removeRes($temp_subj)
	{
		return preg_replace('/(^(([Rr][Ee])(\s)*:)((\s)*([Rr][Ee])(\s)*:)*)/','',trim($temp_subj));
	}

	//Function to check whether the Job Order is posted to Job Boards - Kiran Alli
	function JobOrder_postJobBoards($posid,$chkAdmin='')
	{
		//here the posid is the job order id which is used in the query
		global $username,$maildb,$db;

		if($chkAdmin==1)
		{
			$query = "SELECT DISTINCT jba.sno,IF (jbad.status = 'P', ".tzRetQueryStringDTime("jbad.posted_date","Date","/")." , IF (jbad.status = 'R', ".tzRetQueryStringDTime("jbad.refresh_date","Date","/")." , IF ((jbad.status = 'RM' OR jbad.status = 'RP' OR jbad.status = 'UP'), '', ".tzRetQueryStringDTime("jbad.posted_date","Date","/")."))) dt
			FROM jobboard_accounts AS jba
			LEFT JOIN jobboards AS jb ON jb.sno = jba.jobboard_id
			LEFT JOIN jobboard_access_details AS jbad ON jba.sno = jbad.jobboardacc_id
			WHERE jba.status != 'INACTIVE'
			AND jbad.posid = '$posid'
			AND jbad.status NOT IN('RM','RP','UP')
			AND jb.jobboard_name != ''
			ORDER BY dt DESC";
		}
		else
		{
			$query = "SELECT DISTINCT jba.sno,IF (jbad.status = 'P', ".tzRetQueryStringDTime("jbad.posted_date","Date","/")." , IF (jbad.status = 'R', ".tzRetQueryStringDTime("jbad.refresh_date","Date","/")." , IF ((jbad.status = 'RM' OR jbad.status = 'RP' OR jbad.status = 'UP'), '', ".tzRetQueryStringDTime("jbad.posted_date","Date","/")."))) dt
			FROM jobboard_accounts AS jba
			LEFT JOIN jobboards AS jb ON jb.sno = jba.jobboard_id
			LEFT JOIN jobboard_access_details AS jbad ON jba.sno = jbad.jobboardacc_id
			WHERE jba.contact_id='$username'
			AND jba.status != 'INACTIVE'
			AND jbad.posid = '$posid'
			AND jbad.status NOT IN('RM','RP','UP')
			AND jb.jobboard_name != ''
			ORDER BY dt DESC";
		}

		$result=mysql_query($query,$db);
		$nos = mysql_num_rows($result);

		if($nos ==0)
		{
			$jobBoardMsg="<font size='0.2px'></font><label class='labels'><a href=\"javascript:doManageJB('Job Boards','$posid','A')\"><font class=editlinkrow1>Post to Job Boards</font></a></label>";
		}
		else
		{
			$row = mysql_fetch_row($result);
			if($row[1])
				$lastJbPosteddate = '['.$row[1].']';
			else
				$lastJbPosteddate ='';
			$jobBoardMsg="<font size='0.2px'></font><label class='labels'>Posted to Job Boards $lastJbPosteddate </label><a href=\"javascript:doManageJB('Job Boards','$posid','E')\"><font class='editlinkrow1' style='vertical-align:middle'>View List</font></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"javascript:pullCand('$posid')\"><font class='editlinkrow1' style='vertical-align:middle'>Pull Candidates</font></a>";
		}
		return $jobBoardMsg;
	}

	//Search for the resumes from datafrenzy based on city, state, postal code and job description
	function jobOrderSearchResumes($posid)
	{
		$jobBoardMsg="<font size='0.2px'></font><label class='labels'><a href=\"javascript:doManageDF('$posid')\"><font class=editlinkrow1>Candidate Source</font></a></label>";
		return $jobBoardMsg;
	}


	//removal of employee ids Handler
	function removeJobBoardIdsHandler($total_ids,$ids_to_search)
	{
		/**
		* This function takes 2 Arguments of which the first argument is the total ids list and the
		* second argument is the list of ids to remove from the first argument list and returns the new ids
		* after removal, the total count of new ids and the removed ids as a pipe separated value.
		**/
		$arrIds = explode(",",$total_ids);
		$arrVals = explode(",",$ids_to_search);
		$newIds = array_diff($arrIds,$arrVals);
		$newIdLen = count($newIds);
		$stringVal = implode(",",$newIds);
		$backup_ids = array_diff($arrIds,$newIds);
		$backup_val = implode(",",$backup_ids);
		return $stringVal."|".$newIdLen."|".$backup_val;
	}
	//handler function ends here

	//A function for the removal of employee ids from jobBoard Access Groups when terminated/Deactivated - Sandeep Boora
	function removeJobBoardIds($set_of_ids,$num_ids=0)
	{
		/**
		* here the first parameter is "the list of ids to remove" and the optional second parameter is "either 0 or 1" which
		* indicates single or multiple ids.
		**/

		global $maildb,$db;

		$selGroups = "SELECT username FROM emp_list WHERE sno IN (".$set_of_ids.")";
		$resGroups = mysql_query($selGroups,$db);
		while($infoGroups = mysql_fetch_row($resGroups))
		{
			$dque="DELETE FROM jobboard_accounts WHERE contact_id='".$infoGroups[0]."'";
			//mysql_query($dque,$db);
		}
	}

	//This fun for getting sno-name associative array by passing type to manage table...
	function getManageSnoNames($type)
	{
		global $maildb,$db;

		$que="SELECT sno,name FROM manage WHERE type='".$type."' order by name";
		$res=mysql_query($que,$db);
		while($row=mysql_fetch_row($res))
		{
			$category[$row[0]]=$row[1];
		}
		return $category;
	}
	//	This function is use ful on contacts grid display...

	function convertCategory($catid)
	{
		global $category;

		$final="";
		if($catid!="")
		{
			$scatid=explode(",",$catid);
			foreach($scatid as $key => $value)
			{
				if($category[$value] != '')
					$store[$key]=$category[$value];
			}
			$final=implode(",",$store);
			unset($store);
		}
		return $final;
	}
	//This function is used to dump the accounting company data to hrcon_w4, empcon_w4 and new_w4 tables -- kumar raju k.
	function dumpW4TableData($opprCompId,$accCompId) //Send these ( $opprCompId = staffoppr_cinfo.sno and $accCompId = staffacc_cinfo.username ) values to function.
	{
		global $maildb,$db;

		if($opprCompId != "" && $accCompId == "") //Checking the staffoppr_cinfo.sno is null or not
		{
			$sel_Qry = "SELECT acc.username FROM staffoppr_cinfo oppr, staffacc_cinfo acc WHERE oppr.sno = acc.crm_comp AND oppr.acc_comp = acc.sno AND oppr.sno = '".$opprCompId."'";
			$res_Qry = mysql_query($sel_Qry,$db);
			$row_Qry = mysql_fetch_row($res_Qry);

			$companyId = $row_Qry[0];
		}
		else if($opprCompId == "" && $accCompId != "") //Checking the staffacc_cinfo.username is null or not
		{
			$companyId = $accCompId;
		}

		if($companyId != "") //Checking for staffacc_cinfo.username is there or not.
		{
			$selVen_Qry = "SELECT empid FROM vendorsubcon WHERE venid = '".$companyId."' AND venid != ''";
			$resVen_Qry = mysql_query($selVen_Qry,$db);

			while($rowVen_Qry = mysql_fetch_row($resVen_Qry))
			{
				$updHR_Qry = "UPDATE hrcon_w4 w4, staffacc_cinfo cinfo SET w4.business_name = cinfo.cname,  w4.address1 = cinfo.address1, w4.address2 = cinfo.address2, w4.city = cinfo.city, w4.state = cinfo.state, w4.zip = cinfo.zip WHERE w4.username = '".$rowVen_Qry[0]."' AND cinfo.username = '".$companyId."' AND w4.ustatus = 'active'";
				mysql_query($updHR_Qry,$db);

				$updEMP_Qry = "UPDATE empcon_w4 w4, staffacc_cinfo cinfo SET w4.business_name = cinfo.cname,  w4.address1 = cinfo.address1, w4.address2 = cinfo.address2, w4.city = cinfo.city, w4.state = cinfo.state, w4.zip = cinfo.zip WHERE w4.username = '".$rowVen_Qry[0]."' AND cinfo.username = '".$companyId."'";
				mysql_query($updEMP_Qry,$db);

				$updNET_Qry = "UPDATE net_w4 w4, staffacc_cinfo cinfo SET w4.business_name = cinfo.cname,  w4.address1 = cinfo.address1, w4.address2 = cinfo.address2, w4.city = cinfo.city, w4.state = cinfo.state, w4.zip = cinfo.zip WHERE w4.username = '".$rowVen_Qry[0]."' AND cinfo.username = '".$companyId."' AND w4.ustatus = 'active'";
				mysql_query($updNET_Qry,$db);
			}
		}

		return;
	}
	//fetch hrcon_w4 details...-prasadd
	function insertUpdateConsultingVendor($appuser,$candUsername)
	{
		global $maildb,$db,$username;

		$sel = "SELECT  venid FROM vendorsubcon WHERE empid='".$appuser."'";
		$res = mysql_query($sel,$db);
		$res_row_count = mysql_num_rows($res);
		$res_row = mysql_fetch_row($res);

		$taxSel = "SELECT sno,tax,business_name,address1,address2,city,state,zip FROM hrcon_w4 WHERE username='".$appuser."' AND ustatus='active'";
		$result = mysql_query($taxSel,$db);
		$result_row_count = mysql_num_rows($result);
		$result_row = mysql_fetch_row($result);

		$sel_staffacc_sno="SELECT sc.sno FROM staffacc_cinfo sc,staffacc_list sl WHERE sl.username='".$res_row[0]."' AND sc.username=sl.username AND sl.status='ACTIVE'";
		$res_sno=mysql_query($sel_staffacc_sno,$db);
		$fetch_sno=mysql_fetch_row($res_sno);
		$csno=$fetch_sno[0];

		if($result_row_count > 0 && $result_row[1] == "C-to-C")
		{ 
		       if(trim($result_row[2]) !="" || trim($result_row[3]) !="" || trim($result_row[4]) !="" || trim($result_row[5]) !="" || trim($result_row[6]) !="" || trim($result_row[7]) !="")				{ 
					//insert block..
					/** sanghamitra: get the compstatus **/
					$comStatusQue= mysql_query("select sno from manage where name LIKE '%vendor%' AND type='compstatus'  AND status='Y'",$db);
                    $comStatusRes = mysql_fetch_row($comStatusQue);
                    $compStatus = $comStatusRes[0];	
					$hrmDeptID = getOwnerDepartment();
					$insquery="INSERT INTO staffacc_list (approveuser, status, stime)  VALUES ('".$username."','ACTIVE',NOW())";
					mysql_query($insquery,$db);
					$lastInsertId = mysql_insert_id($db);
					$consultUserName="acc".$lastInsertId;

					$updateque="UPDATE staffacc_list SET username='".$consultUserName."' WHERE sno=".$lastInsertId;
					mysql_query($updateque,$db);

					$insSql="INSERT INTO staffacc_cinfo (username,cname,address1,address2,city,state,zip,billing_default,cust_classid,vend_classid,compstatus) SELECT '".$consultUserName."',hrcon_w4.business_name,hrcon_w4.address1,hrcon_w4.address2,hrcon_w4.city,hrcon_w4.state,hrcon_w4.zip,'Y','".DEFAULT_DEPT_CLASS."','".DEFAULT_DEPT_CLASS."','".$compStatus."' FROM hrcon_w4 WHERE hrcon_w4.sno='".$result_row[0]."'";
					mysql_query($insSql,$db);
					$csno=mysql_insert_id($db);

					
                    
					/** Vendor Sync from HRM to CRM : Inserting vendor in CRM companies table (i.e staffoppr_cinfo) **/
					$inscrmSql=" INSERT INTO staffoppr_cinfo (sno,approveuser,cname,address1,address2,city,state,zip,acc_comp,industry,curl,ctype,csize,nloction,nbyears,nemployee,com_revenue,federalid,status,accessto,phone,fax,keytech,siccode,ticker,csource,department,parent,muser,mdate,cdate,owner,compowner,compbrief,compsummary,compstatus,bill_contact,bill_address,bill_req,service_terms,dress_code,tele_policy,smoke_policy,parking,park_rate,directions,culture, alternative_id,phone_extn,address_desc,deptid) SELECT '','".$username."',hrcon_w4.business_name,hrcon_w4.address1,hrcon_w4.address2,hrcon_w4.city,hrcon_w4.state,hrcon_w4.zip,'".$csno."','','','','','','','','','','ER','ALL','','','','','','','','','','".$username."','".$username."','".$username."','','','','".$compStatus."','','','','','','','','','','','','','','','".$hrmDeptID."' FROM hrcon_w4 WHERE hrcon_w4.sno='".$result_row[0]."'";
					
					mysql_query($inscrmSql,$db);
					$oppr_csno=mysql_insert_id($db);

					$up_qry_oppr = "UPDATE staffoppr_cinfo SET customerid = '".$oppr_csno."', bill_address = '".$oppr_csno."', muser='".$username."', mdate=NOW() WHERE sno = '".$oppr_csno."'";
					mysql_query($up_qry_oppr,$db);
					
					$up_qry_acc = "UPDATE staffacc_cinfo SET customerid = '".$csno."', bill_address = '".$csno."', type = 'CV', muser='".$username."', mdate=NOW(),crm_comp='".$oppr_csno."' WHERE sno = '".$csno."'";
					mysql_query($up_qry_acc,$db);

					setDefaultAccInfoAcc("customer",$db,$csno);
					setDefaultAccInfoAcc("CV",$db,$csno);
					/**sanghamitra: if creating a new vendor in HRM then need to create a new instead of updating the previous vendor. **/
					if($res_row_count == 0)
			        { 
						$que="INSERT INTO vendorsubcon (subid,venid,empid) VALUES('".$candUsername."','".$consultUserName."','".$appuser."')";
					mysql_query($que,$db);
                    }else{ 
                       $que="UPDATE vendorsubcon SET venid = '".$consultUserName."' WHERE  subid='".$candUsername."' AND empid='".$appuser."' ";
						mysql_query($que,$db);
					}
					setVendorId($csno,'CV'); // Inserting values into vendors table, it will maintain uniqe id for vendors.
				}

			
		}
		else 
		{ 
			$del_qry = "DELETE FROM vendorsubcon WHERE empid='".$appuser."' AND venid='".$res_row[0]."'";
			mysql_query($del_qry,$db);

			$upd_qry = "UPDATE candidate_list SET supid=0 WHERE username='".$candUsername."'";
			mysql_query($upd_qry,$db);
		}

		return $csno;
	}

	//To insert / update employee pay setup.
	function CreateUpdateEmployeePaysetup($type,$empid,$comp,$period,$hours,$days)
	{
		global $maildb,$db;

		if($type == "insert")
		{
			$dqry = "delete from employee_paysetup where paysetup_username='".$empid."'";
			mysql_query($dqry,$db);
			$iqry = "INSERT INTO employee_paysetup(paysetup_username,payperiod_company,payperiod,stdhours_payperiod, no_days_payperiod) values('".$empid."','".$comp."','".$period."','".$hours."','".$days."')";
			mysql_query($iqry,$db);
		}
		else if($type == "update")
		{
			$uqry = "UPDATE employee_paysetup SET payperiod_company='".$comp."', payperiod='".$period."', stdhours_payperiod='".$hours."', no_days_payperiod='".$days."' where paysetup_username='".$empid."'";
			mysql_query($uqry,$db);
		}
	}

	//Getting state_id from state_codes table using abbrevation
	function getStateIdAbbr($stateAbbr,$countryName)
	{
		global $maildb,$db;

		if($countryName == '243')
		{
			$sel_stateQry = "SELECT state_id FROM state_codes WHERE state_abbr = '".$stateAbbr."' OR state_name = '".$stateAbbr."'";
		}
		else
		{
			$sel_stateQry = "SELECT state_id FROM state_codes WHERE state_abbr = '".$stateAbbr."'";
		}

		$sel_stateRes = mysql_query($sel_stateQry,$db);
		$sel_stateNum = mysql_num_rows($sel_stateRes);

		if($sel_stateNum > 0)
		{
			$sel_stateRow = mysql_fetch_row($sel_stateRes);
			$stateAbbrId = $sel_stateRow[0];
			$stateIdCode = "";
		}
		else
		{
			$stateIdCode = $stateAbbr;
			$stateAbbrId = 0;
		}

		return $stateIdCode."|AKKENEXP|".$stateAbbr."|AKKENEXP|".$stateAbbrId;
	}

	//Function for checking if candidate with same name or email id exists -- For candidate duplicate checking in Resume Parsing.
	function candidateDuplicateChecking($candFname,$candLname,$candEmailCheck,$Navigate_Module)
    {
        global $maildb,$db,$username;

        $candidateNameEmail = "";
        $candDuplicateStatus = "no";
        if($candEmailCheck=="" && $candFname=="" && $candLname=="")
            $candDuplicateStatus = "no";
        else
        {
            if($candFname!="" && $candLname!="" && $candEmailCheck!="")
            {
                $candidateNameEmail = " AND ((fname='".$candFname."' AND lname='".$candLname."') OR email='".$candEmailCheck."') ";
            }
            else if($candEmailCheck!="" && ($candFname=="" || $candLname==""))
            {
                $candidateNameEmail=" AND email='".$candEmailCheck."' ";
            }
            else if(($candFname!="" || $candLname!="") && $candEmailCheck=="")
            {
                if($candFname!="" && $candLname!="")
                    $candidateNameEmail = " AND ((fname='".$candFname."' AND lname='".$candLname."')) ";
                else if($candFname!="")
                    $candidateNameEmail = " AND fname='".$candFname."' ";
                else if($candLname!="")
                    $candidateNameEmail = " AND lname='".$candLname."' ";
            }

            if($Navigate_Module=='Admin_Candidates')
                $candidate_access_condition = "";
            else
                $candidate_access_condition = " AND (FIND_IN_SET('$username',accessto)>0 OR owner='$username' OR accessto='ALL') ";

            if($candidateNameEmail!="")
            {
                //Checking candidate name or emailid is existing in CRM --- candidate_general table.
                $chkCrmQue="select sno,email from candidate_list where status='ACTIVE' $candidate_access_condition $candidateNameEmail ";
                $chkCrmRes=mysql_query($chkCrmQue,$db);
                $chkCrmCount=mysql_num_rows($chkCrmRes);

                if($chkCrmCount>0)
                    $candDuplicateStatus = "yes";
            }
        }

        return $candDuplicateStatus;
    }

    //Checking record access value for the companies and contact.
	function chkAvailAccessTo($accessVal,$statusVal,$getUser)
	{
		if($statusVal == "ER")
		{
			if($accessVal == "ALL")
				$showAccessTo = "Yes";
			else
			{
				if(strpos($accessVal,',') > -1)
				{
					$expAccessVal = explode(",",$accessVal);

					if(in_array($getUser,$expAccessVal))
						$showAccessTo = "Yes";
					else
						$showAccessTo = "No";
				}
				else if($accessVal == $getUser)
					$showAccessTo = "Yes";
				else
					$showAccessTo = "No";
			}
		}
		else
		{
			$showAccessTo = "No";
		}

		return $showAccessTo;
	}

	//Getting jobboard_name from jobboards table
	function getJobBoardName($serialNum, $status)
	{
		global $maildb,$db;

		$sqryjobboards = "SELECT jobboard_name FROM jobboards WHERE sno=".$serialNum." AND status='".$status."'";
		$srsjobboards = mysql_query($sqryjobboards,$db);
		$srowjobboards = mysql_fetch_row($srsjobboards);

		return $srowjobboards[0];
	}

	//Getting source_name from jobsourcing table
	function getJobSourcingName($serialNum, $status)
	{
		global $maildb,$db;

		$sqryjobsourcing = "SELECT source_name FROM jobsourcing WHERE sno=".$serialNum." AND status='".$status."'";
		$srsjobsourcing = mysql_query($sqryjobsourcing,$db);
		$srowjobsourcing = mysql_fetch_row($srsjobsourcing);

		return $srowjobsourcing[0];
	}

	// To get assignment pusername by inserting into assignment_sequence table
	function get_Assginment_Seq()
	{
		global $maildb,$db;

		$ins_asg_seq="INSERT INTO assignment_sequence(seq_id) VALUES('')";
		mysql_query($ins_asg_seq,$db);
		$assg_seq_num=mysql_insert_id($db);

		$puser=get_Assginment_Id($assg_seq_num);

		$upd_assg_seq="UPDATE assignment_sequence SET assign_no='".$puser."' WHERE seq_id='".$assg_seq_num."'";
		mysql_query($upd_assg_seq,$db);

		$hcount = mysql_num_rows(mysql_query("SELECT sno FROM hrcon_jobs WHERE pusername='$puser' OR assign_no='$puser'",$db));



		$ccount = mysql_num_rows(mysql_query("SELECT sno FROM consultant_jobs WHERE pusername='$puser' OR assign_no='$puser'",$db));
		$pcount = mysql_num_rows(mysql_query("SELECT sno FROM placement_jobs WHERE pusername='$puser' OR assign_no='$puser'",$db));

	
		if($hcount==0 && $ccount==0 && $pcount==0)
			return $puser;
		else
			return get_Assginment_Seq();
	}

	//Function to update assignment schedule
	function updateAssignmentSchedule($updassignment_array)
	{
		global $username,$maildb,$db;

		$upd_tab="UPDATE assignment_schedule SET invapproved='".$updassignment_array['invapproved']."',mdate=NOW(),muser='".$username."' WHERE ";
		if($updassignment_array['userid']!="")
		{
			 $upd_tab.=$concat_Var.$updassignment_array['userid'];
			 $concat_Var=" AND ";
		}
		if($updassignment_array['modulename']!="")
		{
			 $upd_tab.=$concat_Var." modulename='".$updassignment_array['modulename']."'";
			 $concat_Var=" AND ";
		}
		if($updassignment_array['contactsno']!="")
		{
			 $upd_tab.=$concat_Var.$updassignment_array['contactsno'];
			 $concat_Var=" AND ";
		}
		if(trim($updassignment_array['appno'])!="")
		{
			 $upd_tab.=$concat_Var.$updassignment_array['appno'];
			 $concat_Var=" AND ";
		}
		mysql_query($upd_tab,$db);
		return mysql_affected_rows($db);
	}

	function updatePusernameSchedule($id,$puser)
	{
		global $maildb,$db;

		if($id)
		{
			$upd_tab="UPDATE assignment_schedule SET pusername='".$puser."' WHERE appno='".$id."'";
			mysql_query($upd_tab,$db);
		}
	}

	//Function to insert assignment schedule
	function insertAssignmentSchedule($assignment_array)
	{
		global $username,$maildb,$db;

		$field_names="userid,contactsno,modulename,invapproved,recstatus,cdate,cuser,mdate,muser,startdate,enddate,title";
		$field_values="'".$assignment_array['userid']."','".$assignment_array['contactsno']."','".$assignment_array['modulename']."','".$assignment_array['invapproved']."','active',NOW(),'".$username."',NOW(),'".$username."','".$assignment_array['startdate']."','".$assignment_array['enddate']."','".addslashes($assignment_array['title'])."'";

		if($assignment_array['modulename'] == 'HR->Assignments')
		{
			$field_names.=",assign_no";
			$field_values.=",'".$assignment_array['pusername']."'";
		}

		$ins_assignment_schedule="INSERT INTO assignment_schedule (".$field_names.") VALUES (".$field_values.")";
		mysql_query($ins_assignment_schedule,$db);
		$assignment_id=mysql_insert_id($db);

		return $assignment_id;
	}

	//for accounts location departments...
	function getDeptOptions($loc_id,$selval)
	{
		global $maildb,$db;

		$result = "";

		$sqry = "SELECT sno,if(depcode!='',CONCAT_WS(' - ',depcode,deptname),deptname) FROM department WHERE (loc_id = '".$loc_id."' OR deflt = 'Y') AND status='Active' ORDER BY deflt";
		$srs  = mysql_query($sqry,$db);
		while($srow = mysql_fetch_row($srs))
		{
			$result.="<option value='".$srow[0]."' ".compose_sel($selval,$srow[0]).">".$srow[1]."</option>";
		}

		return $result;
	}

	function getDefaultAccInfo($candid,$db)
	{
	 	$usernames = explode(",",$candid);
	 	$totcount = count($usernames);

	 	for($i=0;$i<$totcount;$i++)
	 	{
			$sqry = "SELECT tax FROM hrcon_w4 where username='".$usernames[$i]."' and ustatus='active'";
			$srs  = mysql_query($sqry,$db);
			$srow = mysql_fetch_row($srs);

			$compensqry = "SELECT sno, dept FROM hrcon_compen WHERE username='".$usernames[$i]."' AND ustatus='active'";
			$compensrs  = mysql_query($compensqry,$db);
			$compensrow = mysql_fetch_row($compensrs);

			$tax_type  = $srow[0];
			$compen_id  = $compensrow[0];
			$dept_id = $compensrow[1];

			$getDptAccounts = "SELECT classid, income_acct, expense_acct, ar_acct, ap_acct, payliability_acct, payexpense_acct FROM department_accounts WHERE deptid = '".$dept_id."' AND status = 'ACTIVE'";
			$resDptAccounts = mysql_query($getDptAccounts,$db);
			$rowDptAccounts = mysql_fetch_array($resDptAccounts);

			$income = $rowDptAccounts['income_acct'];						//DEFAULT_INCOME_ACCOUNT;//'9'

			$liability = '0';
			$payable = '0';

		 	if($tax_type  == "W-2")
		 	{
				$liability = $rowDptAccounts['payliability_acct'];			//DEFAULT_LIABILITY_ACCOUNT;//'6'
				$expense = $rowDptAccounts['payexpense_acct'];				//DEFAULT_EXPENSE_ACCOUNT;//'11'
			}
			else if($tax_type  == '1099')
			{
				$payable = $rowDptAccounts['ap_acct'];						//DEFAULT_PAYABLE_ACCOUNT;//'7'
				$expense = $rowDptAccounts['expense_acct'];					//DEFAULT_VENDOREXPENSE_ACCOUNT;//'12'
			}
			else if($tax_type  == 'C-to-C')
				$expense = $rowDptAccounts['expense_acct'];					//DEFAULT_VENDOREXPENSE_ACCOUNT;//'12'

			$uqry  = "UPDATE employee_accounts SET status='backup',mtime=NOW() WHERE username='".$usernames[$i]."'";
			mysql_query($uqry,$db);

			$iqry = "INSERT INTO employee_accounts(username,income_account,expense_account,liability_account,accounts_payable, status,ctime,mtime,compen_id) VALUES('".$usernames[$i]."','".$income."','".$expense."','".$liability."','".$payable."','active',NOW(),NOW(),'".$compen_id."')";
			mysql_query($iqry,$db);
		}
	}

	//function to get Account Numbers for Compensation Account setup
	function getAccountNumbers($locid,$deptid,$type,$selval,$scatval,$onchange='no')
	{
		global $maildb,$db;
		$result = "";
		if($type=='Income')
			$cat = 8;
		else if($type=='Expense')
			$cat = 10;
		else if($type=='Liability')
			$cat = 4;
		else if($type=='Payable')
			$cat = 5;

		if($selval == "" || $selval == 0  || ($selval == 0 && $scatval == 'Liability') || $onchange == 'yes')
		{
			$getDptAccounts = "SELECT classid, income_acct, expense_acct, ar_acct, ap_acct, payliability_acct, payexpense_acct FROM department_accounts WHERE deptid = '".$deptid."' AND status = 'ACTIVE'";
			$resDptAccounts = mysql_query($getDptAccounts,$db);
			$rowDptAccounts = mysql_fetch_array($resDptAccounts);

			if($type=='Income')
				$selval = $rowDptAccounts['income_acct'];			//9;
			else if($type=='Liability')
				$selval = $rowDptAccounts['payliability_acct'];		//6;
			else if($type=='Payable')
				$selval = $rowDptAccounts['ap_acct'];				//7;
			if($type=='Expense' && $scatval == 'Liability')
				$selval = $rowDptAccounts['payexpense_acct'];			//11;
			else if($type=='Expense' && $scatval == 'Payable')
				$selval = $rowDptAccounts['expense_acct'];		//12;
		}

		/*$sqry = "SELECT tb1.ChartID,tb1.AccountName FROM ac_chart tb1,ac_category tb2,ac_type tb3 WHERE tb1.IsActive='Active' AND tb1.AccountCatRef=tb2.s_no AND tb2.type_ref=tb3.s_no AND tb3.type='".$type."' AND (tb1.deflt='Y' OR (tb1.Location_ref='".$locid."' AND tb1.Department_ref='".$deptid."')) ORDER BY tb1.ChartID";*/

		/*$sqry = "SELECT tb1.ChartID,CONCAT_WS(' - ',tb2.loccode,tb3.depcode,tb1.AccountName) FROM ac_chart tb1,contact_manage tb2,department tb3 WHERE IsActive='Active' AND AccountCatRef='".$cat."' AND tb2.serial_no = tb1.Location_ref AND tb3.sno = tb1.Department_ref AND (tb1.deflt='Y' OR (Location_ref='".$locid."' AND Department_ref='".$deptid."')) ORDER BY ChartID";*/

		/*$sqry = "SELECT tb1.ChartID,CONCAT_WS(' - ',tb2.loccode,tb3.depcode,tb4.name) FROM ac_chart tb1,contact_manage tb2,department tb3,reg_category tb4 WHERE tb4.status='ER' AND tb1.AccountCatRef='".$cat."' AND tb4.sno = tb1.ChartID AND tb2.serial_no = tb1.Location_ref AND tb3.sno = tb1.Department_ref AND ( (Location_ref='".$locid."' AND Department_ref='".$deptid."') OR (Location_ref IN (SELECT cm.serial_no FROM contact_manage cm WHERE cm.deflt='Y') AND Department_ref IN (SELECT dpt.sno FROM department dpt WHERE dpt.deflt='Y'))) ORDER BY tb1.deflt,tb4.name";*/

		$sqry = "SELECT tb1.ChartID,tb4.name FROM ac_chart tb1,reg_category tb4 WHERE tb4.status='ER' AND tb1.AccountCatRef='".$cat."' AND tb4.sno = tb1.ChartID ORDER BY tb1.deflt,tb4.name";

		$srs  = mysql_query($sqry,$db);
		while($srow = mysql_fetch_row($srs))
			$result.= "\t<option value=".$srow[0]." ".compose_sel($selval,$srow[0]).">".$srow[1]."</option>\n";

		return $result;
	}

	//Function to insert/backup in client accounts table while creating/updating a Accounting Customer/Consulting (or) General Vendor
	function ClientAccoutnsTrans($clientArr)
	{
		global $username,$maildb,$db;
		$insbackupVar=0;

		$getDepLoc = "SELECT department.loc_id, department_accounts.classid, department_accounts.income_acct, department_accounts.expense_acct, department_accounts.ar_acct, department_accounts.ap_acct, department_accounts.payliability_acct, department_accounts.payexpense_acct FROM department,department_accounts WHERE department.sno = department_accounts.deptid AND department_accounts.status = 'ACTIVE' AND department.sno = '".$clientArr['deptid']."'";
		$resDepLoc = mysql_query($getDepLoc,$db);
		$rowDepLoc = mysql_fetch_array($resDepLoc);

		$clientArr['loc_id'] = $rowDepLoc['loc_id'];

		if(MANAGE_ACCOUNTS == 'N')
		{
			if($clientArr['clienttype'] == 'CUST')
			{
				$clientArr['acct_receive'] = $rowDepLoc['ar_acct'];
				$clientArr['acct_miscIncome'] = $rowDepLoc['income_acct'];
			}
			else
			{
				$clientArr['acct_payable'] = $rowDepLoc['ap_acct'];
				$clientArr['acct_miscExpense'] = $rowDepLoc['expense_acct'];
			}
		}

		$sel_client_Acc="SELECT sno,loc_id,taxes_pay_acct,acct_receive,acct_payable,acct_miscIncome,acct_miscExpense,deptid FROM Client_Accounts WHERE typeid='".$clientArr['typeid']."' AND clienttype='".$clientArr['clienttype']."' AND status='active'";

		$res_client_Acc=mysql_query($sel_client_Acc,$db);
		$fetch_client_Acc=mysql_fetch_row($res_client_Acc);

		if($fetch_client_Acc[0]=="")
		{
			$insbackupVar = 1;
		}
		else
		{
			if($clientArr['clienttype'] == "CUST")
			{
				if($fetch_client_Acc[3] != $clientArr['acct_receive'])
					$insbackupVar=1;
				else if($fetch_client_Acc[5] != $clientArr['acct_miscIncome'])
					$insbackupVar=1;
			}
			else if($clientArr['clienttype'] == "CV")
			{
				if($fetch_client_Acc[4] != $clientArr['acct_payable'])
					$insbackupVar=1;
				else if($fetch_client_Acc[6] != $clientArr['acct_miscExpense'])
					$insbackupVar=1;
			}
			else if($clientArr['clienttype'] == "GV")
				$insbackupVar=1;

			if($insbackupVar==0)
			{
				if($fetch_client_Acc[1] != $clientArr['loc_id'])
				{
					$upd_client_acc="UPDATE Client_Accounts SET loc_id='".$clientArr['loc_id']."' WHERE sno='".$fetch_client_Acc[0]."'";
					mysql_query($upd_client_acc,$db);
				}
				if($fetch_client_Acc[7] != $clientArr['deptid'])
				{
					$upd_client_acc="UPDATE Client_Accounts SET deptid='".$clientArr['deptid']."' WHERE sno='".$fetch_client_Acc[0]."'";
					mysql_query($upd_client_acc,$db);
				}
			}
			else
			{
				$upd_client_acc="UPDATE Client_Accounts SET status='backup' WHERE sno='".$fetch_client_Acc[0]."'";
				mysql_query($upd_client_acc,$db);
			}
		}

		if($insbackupVar==1)
		{
			$ins_client_acc="INSERT INTO Client_Accounts(sno,username,loc_id,taxes_pay_acct,acct_payable,acct_receive,clienttype,typeid,ctime,mtime,acct_miscIncome,acct_miscExpense,deptid) VALUES('','".$username."','".$clientArr['loc_id']."','".$clientArr['taxes_pay_acct']."','".$clientArr['acct_payable']."','".$clientArr['acct_receive']."','".$clientArr['clienttype']."','".$clientArr['typeid']."',NOW(),NOW(),'".$clientArr['acct_miscIncome']."','".$clientArr['acct_miscExpense']."','".$clientArr['deptid']."')";
			mysql_query($ins_client_acc,$db);
		}

	}

	//Function for insertion of default location,account payable,receivable,Income & Expense Accounts while inserting data into staffacc_cinfo while dumping from other 	tables
	function setDefaultAccInfoAcc($type,$db,$typeid,$deptId=1)
	{
		global $username;

		if($deptId == 0)
			$deptId = 1;

		$getDepLoc = "SELECT department.loc_id, department_accounts.classid, department_accounts.income_acct, department_accounts.expense_acct, department_accounts.ar_acct, department_accounts.ap_acct, department_accounts.payliability_acct, department_accounts.payexpense_acct FROM department,department_accounts WHERE department.sno = department_accounts.deptid AND department_accounts.status = 'ACTIVE' AND department.sno = '".$deptId."'";
		$resDepLoc = mysql_query($getDepLoc,$db);
		$rowDepLoc = mysql_fetch_array($resDepLoc);

		$locid = $rowDepLoc['loc_id'];						//'1';

	    $classId = $rowDepLoc['classid'];

		if($type=='customer')
        {
			$qryUpdateCls = "UPDATE staffacc_cinfo SET cust_classid='".$classId."', muser='".$username."', mdate=NOW() WHERE sno='".$typeid."'";
			$resUpdate = mysql_query($qryUpdateCls,$db);
		}
		else if($type=='CV')
		{
			$qryUpdateCls = "UPDATE staffacc_cinfo SET vend_classid='".$classId."', muser='".$username."', mdate=NOW() WHERE sno='".$typeid."'";
			 $resUpdate = mysql_query($qryUpdateCls,$db);
		}

		if($type == "customer")
		{
			$type = "CUST";
			$taxpayacc = 0; //5
			$accrec = $rowDepLoc['ar_acct'];								//DEFAULT_RECEIVE_ACCOUNT; //1
			$accmisinc = $rowDepLoc['income_acct'];								//DEFAULT_INCOME_ACCOUNT; //9
		}
		else if($type == "vendor" || $type == "GV" || $type == "CV")
		{
			if($type != "GV")
				$type = "CV";
			$accpay = $rowDepLoc['ap_acct'];								//DEFAULT_PAYABLE_ACCOUNT; //7
			$accmisexp = $rowDepLoc['expense_acct'];								//DEFAULT_VENDOREXPENSE_ACCOUNT; //12
		}

		$getActiveRecord = "SELECT COUNT(1) FROM Client_Accounts WHERE clienttype = '".$type."' AND typeid = '".$typeid."' AND status = 'active'";
		$resActiveRecord = mysql_query($getActiveRecord,$db);
		$rowActiveRecord = mysql_fetch_array($resActiveRecord );

		if($rowActiveRecord[0] > 0)
		{
			$updExistingRecord = "UPDATE Client_Accounts SET status = 'backup' WHERE clienttype = '".$type."' AND typeid = '".$typeid."' AND status = 'active'" ;
			mysql_query($updExistingRecord,$db);
		}

		$que="insert into Client_Accounts (sno, username, loc_id, taxes_pay_acct, acct_payable, acct_receive, acct_miscIncome, acct_miscExpense, clienttype, typeid, ctime, mtime, status, deptid ) values ('','$username','$locid','$taxpayacc','$accpay','$accrec','$accmisinc','$accmisexp','$type','$typeid',now(),now(),'active','".$deptId."')";
		mysql_query($que,$db);

		return $classId;
	}

	//function to prepare bill_payrates table data.
	function billPayRates_TimeExp_Data($getRegRate, $getOverRate, $getDoubleRate, $getRegPeriod, $getOverPeriod, $getDoublePeriod, $getVendType, $getPUserName, $getEmpUserName, $getTransType, $getQuantity, $getClientId, $prepareData)
	{
		$getRateType = "";

		if($getRegRate != "")
		{
			if($getTransType == "TS")
				$getRateType = "Regular";

			if($prepareData == "")
				$prepareData = $getRegRate."|".$getRegPeriod;
			else
				$prepareData .= "^".$getRegRate."|".$getRegPeriod;

			$prepareData .= "|".$getQuantity."|".$getRateType."|".$getVendType."|".$getEmpUserName."|".$getPUserName."|".$getTransType."|".$getClientId;
		}

		if($getOverRate != "")
		{
			if($getTransType == "TS")
				$getRateType = "OverTime";

			if($prepareData == "")
				$prepareData = $getOverRate."|".$getOverPeriod;
			else
				$prepareData .= "^".$getOverRate."|".$getOverPeriod;


			$prepareData .= "|".$getQuantity."|".$getRateType."|".$getVendType."|".$getEmpUserName."|".$getPUserName."|".$getTransType."|".$getClientId;
		}

		if($getDoubleRate != "")
		{
			if($getTransType == "TS")
				$getRateType = "DoubleTime";

			if($prepareData == "")
				$prepareData = $getDoubleRate."|".$getDoublePeriod;
			else
				$prepareData .= "^".$getDoubleRate."|".$getDoublePeriod;

			$prepareData .= "|".$getQuantity."|".$getRateType."|".$getVendType."|".$getEmpUserName."|".$getPUserName."|".$getTransType."|".$getClientId;
		}

		return $prepareData;
	}

	function getEntityDetails($id, $type)
	{
		global $maildb,$db, $username;
		switch($type)
		{
			case 'ACC':
				$query = "SELECT sno, name AS entityName FROM reg_category WHERE sno = $id";
				break;
			case 'CUST':
			case 'CV':
				$query = "SELECT s.sno, s.cname  AS entityName FROM staffacc_cinfo s, Client_Accounts ca WHERE s.sno = ca.typeid AND ca.sno = $id";
				break;
			case 'GV':
				$query = "SELECT r.sno, r.name  AS entityName FROM reg_payee r, Client_Accounts ca WHERE r.sno = ca.typeid AND ca.sno = $id";
				break;
			case 'EMP':
				$query = "SELECT e.sno, e.name  AS entityName FROM emp_list e, employee_accounts ea WHERE e.username = ea.username AND ea.sno = $id";
				break;
		}

		$sql = mysql_query($query, $db);
		$row = mysql_fetch_array($sql);
		return $row;
	}

	function accountsChildSelBox($acid, $name, $selValue, $deptFilter='')
	{
		global $maildb,$db, $aryOptions;

		$getChild = "SELECT r.sno, r.name, r.type, '', '', acc.tdesc FROM reg_category r, ac_chart ac, ac_category acc WHERE r.sno = ac.ChartID AND r.status = 'ER' AND acc.type = r.type AND r.parent = $acid  ORDER BY  r.name";
		$resChild = mysql_query($getChild,$db);
		if(mysql_num_rows($resChild) > 0)
		{
			while($rowChild = mysql_fetch_array($resChild))
			{
				$rname= $name.":".$rowChild['name'];
				$dispName = $rowChild['name'];
				$glbAccSelected = ($selValue == $rowChild['sno']) ? 'selected' : '';
				$aryOptions[] = "<option value=\"".$rowChild['sno']."\" ".$glbAccSelected." title=\"".html_tls_entities($rname, ENT_QUOTES)."\">".html_tls_entities($dispName, ENT_QUOTES)."</option>";
				accountsChildSelBox($rowChild['sno'], $rname, $selValue);
			}
		}
	}

	function accountsSelBox($selValue = '', $filters = '', $aryNotdisp = '', $deptFilter='')
	{
		global $maildb,$db, $aryOptions, $aryTest;
		$aryOptions = $aryTest = array();
		$actype = '';
		$query = "SELECT r.sno, r.name, r.type, '', '', acc.tdesc FROM reg_category r, ac_chart ac, ac_category acc WHERE r.sno = ac.ChartID AND r.status =  'ER' AND r.parent = 0 AND acc.type = r.type ";
		if($filters != '')
		{
			$query.=" AND r.type IN('".@implode("','",$filters)."')";
		}

		if($aryNotdisp != '')
		{
			$query.=" AND r.type NOT IN('".@implode("','",$aryNotdisp)."')";
		}

		$query.=" ORDER BY acc.tdesc, r.name ";
		$res = mysql_query($query, $db);
		while($row = mysql_fetch_array($res))
		{
			if($actype != $row['type'] && $deptFilter == '')
			{
				$actype = $row['type'];
				$aryOptions[] = '<optgroup label="'.$row['tdesc'].'" style="text-decoration:none; font-style:normal">';
			}

			$dispName = $row['name'];
			$glbAccSelected = ($selValue == $row['sno']) ? 'selected' : '';

			$aryOptions[] = "<option value=\"".$row['sno']."\" ".$glbAccSelected."  title=\"".html_tls_entities($row['name'], ENT_QUOTES)."\">".html_tls_entities($dispName, ENT_QUOTES)."</option>";

			accountsChildSelBox($row['sno'], $row['name'], $selValue, $deptFilter);

			if($actype != $row['type'] && $deptFilter == '')
			{
				$aryOptions[] = '</optgroup>';
			}

		}
		return $aryOptions;
	}

	//function to get options table values from main database
	function getOptionsTableFieldVals($getSelectOptionsTableFields)
	{
		global $maindb,$company_info_sno;

		$optionsReturnArray = array();

		if($getSelectOptionsTableFields != "")
		{
			$main_OptionsQry = "SELECT ".$getSelectOptionsTableFields." FROM options WHERE sno = '".$company_info_sno."'";
			$main_OptionsRes = mysql_query($main_OptionsQry,$maindb);
			$main_OptionsArray = mysql_fetch_array($main_OptionsRes);
		}

		return $main_OptionsArray;
	}

	//get company_info.fid  for location federal ID...
	function getCompanyFid()
	{
		global $maildb,$db;

		$sqry = "SELECT fid FROM company_info";
		$srs  = mysql_query($sqry,$db);
		$srow = mysql_fetch_row($srs);

		return $srow[0];
	}
	//function for Amount display format in case of USD - prasadd
	function getUSDAmountFormat($amount)
	{
		if(trim($amount) != "")
		{
			// Regular expression to check whether the string contains alphabets, special characters except dot comma
			if(preg_match('/([a-z\-\_\!\@\#\$\%\^\&\*\(\)\+\[\]\'\"\;\:\<\>\/\?])/i',$amount))
			{
				$amount = $amount;
			}
			else
			{
				$pos = strpos($amount,'.');
				if($pos === false)
				{
					$amount = number_format(str_replace(',','',trim($amount)));
				}
				else
				{
					$decimalVal = substr($amount,$pos+1);
					$decimalValLen = strlen($decimalVal);
					$integerVal = trim(substr($amount,0,-$decimalValLen));
					$amount = number_format(str_replace(',','',$integerVal)).".".$decimalVal;
				}
			}
			return $amount;
		}
		else
			return "";
	}
	function getAccountFullNameLocDep($sno)
	{
		global $maildb,$db;

		$sqry = "SELECT reg_category.name FROM reg_category,ac_chart, contact_manage, department WHERE reg_category.sno = '".$sno."' AND ac_chart.Location_ref = contact_manage.serial_no AND department.sno = ac_chart.Department_ref AND reg_category.sno = ac_chart.ChartID AND department.status='Active'";
		$srs  = mysql_query($sqry,$db);
		$srow = mysql_fetch_row($srs);

		return $srow[0];
	}
	function getWorkersCompCode($wcid)
	{
		global $maildb,$db;

		$sqry = "SELECT CONCAT_WS('-',code,title,state) FROM workerscomp WHERE workerscompid = '".$wcid."' AND status = 'active' ORDER BY CONCAT_WS('-',code,title,state)";
		$srs  = mysql_query($sqry,$db);
		$srow = mysql_fetch_row($srs);

		return $srow[0];
	}

	function getBillPayTermsCode($termsId, $termsType)
	{
		global $maildb,$db, $username;

		$billpayCode = "";
		$selQry = "SELECT billpay_code FROM bill_pay_terms WHERE billpay_termsid = '".$termsId."' AND billpay_type = '".$termsType."' AND billpay_status = 'active'";
		$resQry = mysql_query($selQry, $db);

		if($resQry)
		{
			$rowQry = mysql_fetch_row($resQry);
			$billpayCode = $rowQry[0];

		}

		return stripslashes($billpayCode);
	}

	//Job order summery screens-billing info section amounts period display--prasadd

	//UOM: to display UOM ratetypes
	function amountDisplayPeriod($periodVal)
	{
		if($periodVal == 'FLATFREE' || $periodVal == 'FLATFEE')
			$periodVal = 'FLAT FEE';
		else if($periodVal == 'MONTHLY')
			$periodVal = 'MONTH';
		else if($periodVal == 'WEEKLY')
			$periodVal = 'WEEK';
		else if($periodVal == 'YEARLY')
			$periodVal = 'YEAR';
		else if($periodVal == 'HOURLY')
			$periodVal = 'HOUR';
		else if($periodVal == 'UOM_DAY')
			$periodVal = 'DAY*';
		else if($periodVal == 'UOM_MILE')
			$periodVal = 'MILE*';
		else if($periodVal == 'UOM_UNIT')
			$periodVal = 'UNIT*';

		return ($periodVal);
	}

	//Getting parent field names for accounts in accounting
	function getParentAccBankNames()
	{
		global $maildb,$db;
		$getValues = "";

		$que = "SELECT reg_category.sno AS sno, reg_category.name AS name, reg_category.parent AS parent, ac_category.type_ref AS type
		FROM reg_category, ac_chart
		LEFT JOIN ac_category ON ac_category.s_no=ac_chart.AccountCatRef
		WHERE ac_chart.ChartID = reg_category.sno
		AND reg_category.status IN ('ER')";
		$res = mysql_query($que,$db);

		while($row = mysql_fetch_array($res))
		{
			$parent = "";
			$balance = 0;
			if($row['parent'] != 0)
				$parent = getAccBankParent($row['parent'],"");

			if($getValues != "")
				$getValues .= "^".$row['sno']."|".$parent;
			else
				$getValues = $row['sno']."|".$parent;

			$balance = getAccBankTransTotal($row['sno']);
			$balanceAccounts = getAccBankBalance($row['sno'],$balance,$row['sno']);

			$balAccArray = explode("^",$balanceAccounts);

			$getValues .= "|".$balAccArray[0]."|".$balAccArray[1]."|".$row['type'];
		}
		return $getValues;
	}

	//Getting parents for accounts in accounting
	function getAccBankParent($parent,$parent_name)
	{
		global $maildb,$db;

		$pque = "SELECT sno, name, parent FROM reg_category WHERE sno='".$parent."'";
		$pres = mysql_query($pque,$db);
		$prow = mysql_fetch_array($pres);

		if($parent_name != "")
			$parent_name = $parent_name." : ".$prow['name'];
		else
			$parent_name = $prow['name'];

		if($prow['parent'] != 0)
			return getAccBankParent($prow['parent'],$parent_name);

		return $parent_name;
	}

	//Getting balance for accounts in accounting
	function getAccBankBalance($account,$total,$accountIds)
	{
		global $maildb,$db;

		$getChild = "SELECT sno FROM reg_category WHERE parent = '".$account."' AND status = 'ER'";
		$resChild = mysql_query($getChild,$db);

		if(mysql_num_rows($resChild) > 0)
		{
			while($rowChild = mysql_fetch_array($resChild))
			{
				$total += getAccBankTransTotal($rowChild[0]);
				$accountIds .= ",".$rowChild[0];
				$totalAccounts = getAccBankBalance($rowChild[0],$total,$accountIds);
				$totalAccountsArray = explode("^",$totalAccounts);
				$total = $totalAccountsArray[0];
				$accountIds = $totalAccountsArray[1];
			}
		}
		return $total."^".$accountIds;
	}

	//Getting balance for accounts in accounting
	function getAccBankTransTotal($account)
	{
		global $maildb,$db;

		$getTotal = "SELECT SUM(amount) FROM acc_transaction WHERE accountRefId = '".$account."' AND status = 'ACTIVE' GROUP BY accountRefId";
		$resTotal = mysql_query($getTotal,$db);
		$rowTotal = mysql_fetch_array($resTotal);

		return $rowTotal[0];
	}

	/*
		Below function will return the Entity Display style,
		1 => ID - Name.
		2 => (ID) Name.
		3 => ID - Name ( returns as it is)
	*/
	function getEntityDispName($disId, $disName, $disType = 1)
	{
		$returnValue = '';

		switch($disType)
		{
			case '1':
				$returnValue = "CONCAT_WS(' - ',".$disId.",".$disName.")";
			break;
			case '2':
				$returnValue = "CONCAT('(',".$disId.",') ',".$disName.")";
			break;
			case '3':
				$returnValue = "CONCAT(".$disName.",' (',".$disId.",')')";
			break;
		}

		return $returnValue;
	}

	/*
		Below function will return the Entity Display style for Headings,
		1 => ID - Name.
		2 => (ID) Name.
	*/

	function getEntityDispHeading($disId, $disName, $disType = 1)
	{
		$returnValue = '';

		switch($disType)
		{
			case '1':
				$returnValue = $disId." - ".$disName;
			break;
			case '2':
				$returnValue = "(".$disId.") ".$disName;
			break;
		}

		return $returnValue;
	}

	//Setting Vendor Id
	function setVendorId($id,$type)
	{
		global $maildb,$db;

		$getVenId = "SELECT sno FROM vendors WHERE vendorid = '".$id."' AND vendortype = '".$type."'";
		$resVenId = mysql_query($getVenId,$db);

		if(mysql_num_rows($resVenId) <= 0)
		{
			$insVenId = "INSERT INTO vendors(sno, vendorid, vendortype) VALUES ('','".$id."','".$type."')";
			mysql_query($insVenId,$db);
		}
	}

	//Getting Vendor Id
	function getVendorId($id,$type)
	{
		global $maildb,$db;

		$getVenId = "SELECT sno FROM vendors WHERE vendorid = '".$id."' AND vendortype = '".$type."'";
		$resVenId = mysql_query($getVenId,$db);
		$rowVendId = mysql_fetch_array($resVenId);

		return $rowVendId[0];
	}

	//Function for updating default customer address as billing address, when there is no billing address for that customer.
	function getUpdateDefaultBilling($getCompanySno, $getType = 1, $getWhereCond = 0)
	{
		global $maildb,$db,$username;

		switch($getType)
		{
			case '1':
				$selCompQry = "SELECT bill_address, bill_contact, sno FROM staffacc_cinfo WHERE sno = '".$getCompanySno."' AND bill_address != sno";
				$selCompRes = mysql_query($selCompQry, $db);

				if(mysql_num_rows($selCompRes))
				{
					$selCompRow = mysql_fetch_row($selCompRes);
					if($selCompRow[0] != 0 && $selCompRow[0] != "")
						return getUpdateDefaultBilling($selCompRow[0], $getType);
					else
					{
						$selContQry = "SELECT sno FROM staffacc_contact WHERE csno = '".$selCompRow[2]."' ORDER BY sno ASC";
						$selContRes = mysql_query($selContQry, $db);

						if(mysql_num_rows($selContRes))
							$getBillContact = mysql_fetch_row($selContRes);
						else
							$getBillContact[0] = 0;

						$updQry = "UPDATE staffacc_cinfo SET billing_default = 'Y', bill_address = '".$getCompanySno."', bill_contact = '".$getBillContact[0]."', muser='".$username."', mdate=NOW() WHERE bill_contact = '0' AND (bill_address = '' OR bill_address = '0') AND acccompany = 'Y' AND sno = '".$getCompanySno."'";
						mysql_query($updQry, $db);
					}
				}
			break;
			case '2':
				if($getWhereCond == 0)
					$getWhereCond = "crm_comp";
				else
					$getWhereCond = "sno";

				$selCompQry = "SELECT bill_address, bill_contact, sno FROM staffacc_cinfo WHERE ".$getWhereCond." = '".$getCompanySno."' AND bill_address != sno";
				$selCompRes = mysql_query($selCompQry, $db);

				if(mysql_num_rows($selCompRes))
				{
					$selCompRow = mysql_fetch_row($selCompRes);
					if($selCompRow[0] != 0 && $selCompRow[0] != "")
						return getUpdateDefaultBilling($selCompRow[0], $getType, 1);
					else
					{
						$selContQry = "SELECT sno FROM staffacc_contact WHERE csno = '".$selCompRow[2]."' ORDER BY sno ASC";
						$selContRes = mysql_query($selContQry, $db);

						if(mysql_num_rows($selContRes))
							$getBillContact = mysql_fetch_row($selContRes);
						else
							$getBillContact[0] = 0;

						$updQry = "UPDATE staffacc_cinfo SET billing_default = 'Y', bill_address = sno, bill_contact = '".$getBillContact[0]."', muser='".$username."', mdate=NOW() WHERE bill_contact = '0' AND (bill_address = '' OR bill_address = '0') AND acccompany = 'Y' AND ".$getWhereCond." = '".$getCompanySno."'";
						mysql_query($updQry, $db);

					}
				}
			break;
		}
		$updQry = "UPDATE staffacc_cinfo SET billing_default = 'Y', muser='".$username."', mdate=NOW() WHERE bill_address = sno AND acccompany = 'Y' AND ".$getWhereCond." = '".$getCompanySno."'";
		mysql_query($updQry, $db);
		return;
	}

	function getDispWCOptions($selValue)
	{
		global $maildb,$db;
		$getWCcodes = "SELECT workerscompid,CONCAT_WS('-',code,title,state) FROM workerscomp WHERE status = 'active' ORDER BY CONCAT_WS('-',code,title,state)";
		$resWCcodes = mysql_query($getWCcodes,$db);
		while($rowWCcodes = mysql_fetch_row($resWCcodes))
		{
			$selected = ($rowWCcodes[0] == $selValue) ? ' selected' : '';

			echo "<option value='$rowWCcodes[0]'".$selected.">".html_tls_specialchars($rowWCcodes[1],ENT_QUOTES)."</option>";
		}
	}

	function getBTDiscTotal($value,$subTotalVal,$getChkedTaxAmt)
	{
		$btDiscTotal = 0.00;	// Get before tax discount amount sum...

		foreach($value AS $companyTax)
		{
			if($companyTax[8] == "Discount" && $companyTax[9] == "bt" && $companyTax[0] == "Y" && $companyTax[6] != "")
			{
				if(trim($companyTax[4]) == "%")
				{
					$btDiscTotal = number_format(($btDiscTotal + (($getChkedTaxAmt * $companyTax[3]) /100)),2,'.','');
				}
				else
				{
					$btDiscTotal = number_format($btDiscTotal + (($getChkedTaxAmt * $companyTax[3]) /$subTotalVal),2,'.','');
					//$btDiscTotal = number_format(($btDiscTotal + $companyTax[3]),2,'.','');
				}
			}
		}
		return $btDiscTotal;
	}

	function getBTDiscTotal_Export($companydisc,$subTotal,$timeExpenseCharge_SelTaxAmpount)
	{
		$btDiscTotal = 0.00;	// Get before tax discount amount sum...

		foreach($companydisc AS $discountLine)
		{
			$discountLine_arr = explode("|",$discountLine);

			if($discountLine_arr[7] == "bt")
			{
				if(trim($discountLine_arr[4]) == "%")
				{
					$btDiscTotal = number_format(($btDiscTotal + (($timeExpenseCharge_SelTaxAmpount * $discountLine_arr[3]) /100)),2,'.','');
				}
				else
				{
					$btDiscTotal = number_format($btDiscTotal + (($timeExpenseCharge_SelTaxAmpount * $discountLine_arr[3]) /$subTotal),2,'.','');
					//$btDiscTotal = number_format(($btDiscTotal + $companyTax[3]),2,'.','');
				}
			}
		}
		return $btDiscTotal;
	}

function calculateBillTimesheet($billno, $parid, $assid, $ratetype, $tothours)
{
	global $maildb,$db, $username;
	$bill_timesheet_total_amount = $bill_timesheet_total_hours = 0;
	if($billno =='' || $billno == 0)
		$timesheetBillquery = "IFNULL(timesheet_hours.payroll, '') = ''";
	else
		$timesheetBillquery = "timesheet_hours.payroll = '".$billno."'";

	$querry = "SELECT timesheet_hours.assid AS AsgnID,SUM(IFNULL(timesheet_hours.hours,0)) AS Hours, mht.name AS HoursType,
	if((mht.rateid = 'rate1' OR mht.rateid = 'rate2' OR mht.rateid = 'rate3') , if(mht.rateid = 'rate1', if(hrcon_compen.pay_assign = 'Y', hrmp.rate, hrcon_compen.salary), if(mht.rateid = 'rate2', if(hrcon_compen.assign_overtime='Y', hrmp.rate, hrcon_compen.over_time), if(mht.rateid = 'rate3', if(hrcon_compen.assign_double='Y', hrmp.rate, hrcon_compen.double_rate_amt), hrmp.rate))),hrmp.rate) AS Rate,
	if((mht.rateid = 'rate1' OR mht.rateid = 'rate2' OR mht.rateid = 'rate3') , if(mht.rateid = 'rate1', if(hrcon_compen.pay_assign = 'Y', hrmp.period, hrcon_compen.salper), if(mht.rateid = 'rate2', if(hrcon_compen.assign_overtime='Y', hrmp.period, hrcon_compen.ot_period), if(mht.rateid = 'rate3', if(hrcon_compen.assign_double='Y', hrmp.period, hrcon_compen.double_rate_period), hrmp.period))),hrmp.period) AS Period, hrcon_jobs.sno
	FROM timesheet_hours LEFT JOIN hrcon_compen ON hrcon_compen.username = timesheet_hours.username
	LEFT JOIN hrcon_jobs ON (hrcon_jobs.pusername = timesheet_hours.assid AND hrcon_jobs.username = timesheet_hours.username)
	LEFT JOIN multiplerates_assignment hrmp ON  hrcon_jobs.sno = hrmp.asgnid AND timesheet_hours.hourstype = hrmp.ratemasterid AND hrmp.ratetype = 'payrate' AND hrmp.asgn_mode = 'hrcon'
	LEFT JOIN multiplerates_master mht ON  timesheet_hours.hourstype = mht.rateid AND mht.rateid = hrmp.ratemasterid AND mht.status = 'ACTIVE'
	WHERE ".$timesheetBillquery." AND timesheet_hours.parid='".$parid."' AND timesheet_hours.assid='".$assid."' AND timesheet_hours.hourstype='".$ratetype."'  AND timesheet_hours.status IN ('Approved','Billed')  AND hrcon_compen.ustatus = 'active' AND hrcon_jobs.ustatus IN('active','cancel','closed') AND  timesheet_hours.assid is not null GROUP BY timesheet_hours.parid, AsgnID, timesheet_hours.hourstype  HAVING Hours != 0";

	$result = mysql_query($querry,$db);
	if(mysql_num_rows($result) > 0)
	{
		while($rec = mysql_fetch_array($result))
		{
			$bill_timesheet_total_hours+=$rec['Hours'];
			$regrate = 0 ;
			if($rec['Period'] == 'FLATFEE' && $rec['Hours'] > 0)
				$regrate = $rec['Rate'];
			else if($rec['Period'] != 'FLATFEE')
			{
				$convertedRate = chngToHours($rec['Period'],$rec['Rate'],$tothours);
				$regrate = number_format($rec['Hours'],2,'.','')*number_format($convertedRate,2,'.','');
			}

			$bill_timesheet_total_amount = number_format($bill_timesheet_total_amount+$regrate,2,'.','');
		}
	}

	return $bill_timesheet_total_hours.'|'.$bill_timesheet_total_amount;
}
//Prasadd--Used these functions in timesheet view screens  for class display.
function getClassType($sno)
{
	global $maildb,$db;
	$queryClass = "SELECT classname FROM class_setup WHERE status = 'ACTIVE' AND sno = '".$sno."'";
	$sqlClass = mysql_query($queryClass,$db);
	$rowClass = mysql_fetch_row($sqlClass);
	return $rowClass[0];
}
function getHoursType($rateId)
{
	global $maildb,$db;
	$queryRate = "SELECT multiplerates_master.name FROM multiplerates_master WHERE multiplerates_master.status = 'ACTIVE' AND multiplerates_master.rateid = '".$rateId."'";
	$sqlRate = mysql_query($queryRate, $db);
	$rowRate = mysql_fetch_row($sqlRate);
	return $rowRate[0];
}

function manageInvoiceLines($sno,$db,$invid,$lineid,$linetype,$pusername,$ratetype,$classid,$custom1,$custom2,$chkPusernames = '')
{
	if($sno != "")
	{
		$uqry = "UPDATE invoice_lines SET invid='".$invid."',lineid='".$lineid."',linetype='".$linetype."',pusername='".$pusername."',ratetype='".$ratetype."', classid='".$classid."',custom1='".$custom1."',custom2='".$custom2."' WHERE sno = '".$sno."'";
	}
	elseif($sno == "")
	{
		$asgnValues = explode(",",$chkPusernames);
		if(in_array($pusername,$asgnValues))
		{
			if($linetype == 'TS'){
				$inv_que = "select count(sno) from invoice_lines where invid='".$invid."' and lineid='".$lineid."' and linetype='TS' and pusername='".$pusername."' and ratetype='".$ratetype."' and classid='".$classid."'";
				$inv_res  = mysql_query($inv_que,$db);
				$inv_row = mysql_fetch_row($inv_res);
				if($inv_row[0] == 0){
					$iqry = "INSERT INTO invoice_lines(sno,invid,lineid,linetype,pusername,ratetype,classid,custom1,custom2) VALUES('','".$invid."','".$lineid."', '".$linetype."','".$pusername."','".$ratetype."','".$classid."','".$custom1."','".$custom2."')";
					$irs  = mysql_query($iqry,$db);
				}
			}else{
				$iqry = "INSERT INTO invoice_lines(sno,invid,lineid,linetype,pusername,ratetype,classid,custom1,custom2) VALUES('','".$invid."','".$lineid."', '".$linetype."','".$pusername."','".$ratetype."','".$classid."','".$custom1."','".$custom2."')";
				$irs  = mysql_query($iqry,$db);
			}
		}
	}
}

function updateTimesheetQbid($sno,$db)
{
	$uqry = "UPDATE timesheet_hours SET qbid='time".$sno."' WHERE sno = '".$sno."' AND (TRIM(qbid)='' OR qbid IS NULL)";
	mysql_query($uqry,$db);
}

//Function for updating parent and child relation in the table (To maintain childNode and parentNode if any records has been updated)
function changeParentChildRelation($getTableName, $getSerialNumField, $getSerialNumValue, $getParenNumField)
{
	global $maildb,$db;

	//Getting the parent value from selected table
	$selParQry = 'SELECT '.$getParenNumField.' FROM '.$getTableName.' WHERE '.$getSerialNumField.' = '.$getSerialNumValue;
	$resParQry = mysql_query($selParQry,$db);
	$rowParQry = mysql_fetch_row($resParQry);

	//Checking parent value available or not
	$getParentNumber = ($rowParQry[0] != '') ? $rowParQry[0] : 0;

	//Getting the childs for the particular record from selected table
	$selCldQry = 'SELECT GROUP_CONCAT('.$getSerialNumField.') FROM '.$getTableName.' WHERE '.$getParenNumField.' = '.$getSerialNumValue.' GROUP BY '.$getParenNumField;
	$resCldQry = mysql_query($selCldQry,$db);

	
}

//Function to insert default records for onbench assignment
function insertDefaultPayBillRates($hrsno,$empsno)
{
	global $maildb,$db,$username;

	$ratesStr =  "|||Y|N|payrate|Regular|^||||Y|N|billrate|Regular|^||||Y|N|payrate|OverTime|^||||Y|N|billrate|OverTime|^||||Y|N|payrate|DoubleTime|^||||Y|N|billrate|DoubleTime";
	$exp_rates = explode("|^|",$ratesStr);
	$count_rates=count($exp_rates);

	$rate_ids_arr = array('Regular' => "rate1" , 'OverTime' => "rate2", 'DoubleTime' => "rate3");
	for($i=0;$i<$count_rates;$i++)
	{
		$exp_pay_bill_rates =  explode("|",$exp_rates[$i]);
		$value_set = "('".$hrsno."','hrcon','".$rate_ids_arr[$exp_pay_bill_rates[6]]."','".$exp_pay_bill_rates[5]."','".$exp_pay_bill_rates[0]."','".$exp_pay_bill_rates[1]."','".$exp_pay_bill_rates[2]."','".$exp_pay_bill_rates[3]."','".$exp_pay_bill_rates[4]."','ACTIVE','','".$username."',NOW(),'".$username."',NOW())";

		$ins_rates = "INSERT INTO multiplerates_assignment (asgnid,asgn_mode,ratemasterid,ratetype,rate,period,currency,billable,taxable,status,parent_update, cuser,cdate,muser,mdate)  VALUES ".$value_set;
		mysql_query($ins_rates,$db);


	}
}

/* Setting max length of group_concat */
function setGroupConcatMaxLength()
{
	global $db;

	mysql_query("SET SESSION group_concat_max_len=1073740800",$db);
}

//Save path for report generated files
function getReportsFileSavePath($rptFolder){
	global $WDOCUMENT_ROOT;
	$reportsDir=realpath($WDOCUMENT_ROOT."/");
	if(is_dir($reportsDir))
	{
		if(!is_dir($reportsDir."/reports/"))
		{
			$Ddh=mkdir($reportsDir."/reports/");
			if(!is_dir($reportsDir."/reports/".$rptFolder."/")){
				$dHandler= mkdir($reportsDir."/reports/".$rptFolder."/");
				if($dHandler){
					return $reportsDir."/reports/".$rptFolder."/";
				}else{
					return 'error';
				}
			}else{
				return $reportsDir."/reports/".$rptFolder."/";
			}
		}else{
			if(!is_dir($reportsDir."/reports/".$rptFolder."/")){
				$dHandler= mkdir($reportsDir."/reports/".$rptFolder."/");
				if($dHandler){
					return $reportsDir."/reports/".$rptFolder."/";
				}else{
					return 'error';
				}
			}else{
				return $reportsDir."/reports/".$rptFolder."/";
			}
		}
	}else{
		return 'error1';
	}
}


//Function to have start and end dates for timesheets
// based on the weekend day set in payroll setup
// in Accounting=>Employees=>Pay Employee=>Payroll Setup

function getStartEndDatesBasedOnWeekendDay($callingFrom = '',$dbcon='')
{
	global $maildb,$db, $rptdb;

	$que="SELECT weekend_day FROM cpaysetup WHERE status='ACTIVE'";

	if($callingFrom != 'Reports'){
            $res=mysql_query($que,$db);
        }else {
            if($dbcon == '') {
                $res=mysql_query($que,$rptdb);
            }else {
                $res=mysql_query($que,$db);
            }
	}

	$row=mysql_fetch_row($res);

	$endDate = getLastDate($row[0],date('m/d/Y'));

	$enddateArr = explode('/',$endDate);

	$startDate = date('m/d/Y',mktime(0,0,0,$enddateArr[0],$enddateArr[1]-6,$enddateArr[2]));

	$dateValues = array('StartDate'=>$startDate,'EndDate'=>$endDate);

	return $dateValues;
}

function getLastDate($weekDay,$curDate)
{
	if(is_null($weekDay) || $weekDay == "") $weekDay = date("l");

	$result = $curDate;

	list($mon,$day,$yr) = explode("/",$curDate);

	$tmstmp = mktime(0,0,0,$mon,$day,$yr);

	if(date('l',$tmstmp) != $weekDay) {

		$tmpstmp = strtotime("last $weekDay",$tmstmp);

		$result  = date("m/d/Y",$tmpstmp);
	}

	return $result;
}

function saveInvoiceBillTo($client,$invId,$getAlertForMultipleInvoice,$chkAsgnments)
{
	global $username, $maildb,$db;

	$sqlClientDet = "SELECT staffacc_cinfo.sno,staffacc_cinfo.cname, staffacc_cinfo.address1, staffacc_cinfo.address2, staffacc_cinfo.city, staffacc_cinfo.state, countries.country,staffacc_cinfo.zip,staffacc_cinfo.phone,staffacc_cinfo.fax,staffacc_cinfo.bill_contact,staffacc_cinfo.bill_address,staffacc_cinfo.attention FROM staffacc_cinfo LEFT JOIN countries ON countries.sno = staffacc_cinfo.country WHERE staffacc_cinfo.sno='".$client."'";
	$resClientDet=mysql_query($sqlClientDet,$db);
	$invCustDet=mysql_fetch_row($resClientDet);

	/////////// Adding group by cond//
	$groupByCond = getGroupByCondForInvoiceBillTo($Client);
	//////////////////////////////////
	$asgn_comp_id =0;
	$getAssignmentAddress = "SELECT bill_contact, bill_address, attention  FROM hrcon_jobs WHERE sno IN (".$chkAsgnments.") GROUP BY ".$groupByCond;
	$resAssignmentAddress = mysql_query($getAssignmentAddress,$db);
	$rowAssignmentAddress = mysql_fetch_array($resAssignmentAddress);

	if($rowAssignmentAddress[1] != '0' && $rowAssignmentAddress[1] != '')
	{
		$asgn_comp_id=1;
		$invCustDet[10] = $rowAssignmentAddress[0];
		$invCustDet[11] = $rowAssignmentAddress[1];
	}

	if($rowAssignmentAddress[2] != '')
		$invCustDet[12] = $rowAssignmentAddress[2];

	//Getting Billing Contact details
	$sqlBilCont="select CONCAT_WS(' ',fname,mname,lname),wphone from staffacc_contact where sno='".$invCustDet[10]."' and staffacc_contact.username!=''";
	$resBilCont=mysql_query($sqlBilCont,$db);
	$invBilCont=mysql_fetch_row($resBilCont);

	//Getting Billing contact  Addess details
	$sqlBilAddr="SELECT staffacc_location.address1,staffacc_location.address2,staffacc_location.city,staffacc_location.state,staffacc_location.zipcode,staffacc_location.csno,staffacc_location.ltype,countries.country from staffacc_location LEFT JOIN countries ON countries.sno=staffacc_location.country WHERE staffacc_location.sno='".$invCustDet[11]."'";
	$resBilAddr=mysql_query($sqlBilAddr,$db);
	$invBilAddr=mysql_fetch_row($resBilAddr);

	if($invBilAddr[6]=="con")
	{
		$sqlAddr="select staffacc_contact.wphone,staffacc_cinfo.cname from staffacc_contact LEFT JOIN staffacc_cinfo ON staffacc_cinfo.sno=staffacc_contact.csno where staffacc_contact.sno='".$invBilAddr[5]."'";
		$resAddr=mysql_query($sqlAddr,$db);
		$rowAddr=mysql_fetch_row($resAddr);
	}
	else
	{
		if($asgn_comp_id == 0){
			$sqlAddr="select phone,cname from staffacc_cinfo where sno='".$invCustDet[11]."'";
		}
		else{
			$sqlAddr="select phone,cname from staffacc_cinfo where sno='".$invBilAddr[5]."'";
		}
		$resAddr=mysql_query($sqlAddr,$db);
		$rowAddr=mysql_fetch_row($resAddr);
	}

	$insInvoice_billTo = "INSERT INTO invoice_billto ( sno , invid , Company_Name , Company_Addr1 , Company_Addr2 , Company_City , Company_State , Company_Zip , Company_Country , Company_PH , Company_Fax , Billing_CT , Billing_Addr1 , Billing_Addr2 , Billing_City , Billing_State , Billing_Zip , Billing_Country , Billing_PH , Billing_Comp , Company_Attn ) VALUES ('' , '".$invId."' , '".$invCustDet[1]."' , '".$invCustDet[2]."' , '".$invCustDet[3]."' , '".$invCustDet[4]."' , '".$invCustDet[5]."' , '".$invCustDet[7]."' , '".$invCustDet[6]."' , '".$invCustDet[8]."' , '".$invCustDet[9]."' , '".$invBilCont[0]."' , '".$invBilAddr[0]."' , '".$invBilAddr[1]."' , '".$invBilAddr[2]."' , '".$invBilAddr[3]."' , '".$invBilAddr[4]."' , '".$invBilAddr[7]."' , '".$rowAddr[0]."' , '".$rowAddr[1]."' , '".addslashes($invCustDet[12])."');";
	mysql_query($insInvoice_billTo,$db);

	$errNo = mysql_errno($db);

	if($errNo != '0')
	{
		$insInvoice_billTo_Qry = "INSERT INTO invoice_billto ( sno , invid , Company_Name , Company_Addr1 , Company_Addr2 , Company_City , Company_State , Company_Zip , Company_Country , Company_PH , Company_Fax , Billing_CT , Billing_Addr1 , Billing_Addr2 , Billing_City , Billing_State , Billing_Zip , Billing_Country , Billing_PH , Billing_Comp , Company_Attn ) VALUES ('' , '".$invId."' , '".addslashes($invCustDet[1])."' , '".addslashes($invCustDet[2])."' , '".addslashes($invCustDet[3])."' , '".addslashes($invCustDet[4])."' , '".addslashes($invCustDet[5])."' , '".addslashes($invCustDet[7])."' , '".addslashes($invCustDet[6])."' , '".addslashes($invCustDet[8])."' , '".addslashes($invCustDet[9])."' , '".addslashes($invBilCont[0])."' , '".addslashes($invBilAddr[0])."' , '".addslashes($invBilAddr[1])."' , '".addslashes($invBilAddr[2])."' , '".addslashes($invBilAddr[3])."' , '".addslashes($invBilAddr[4])."' , '".addslashes($invBilAddr[7])."' , '".addslashes($rowAddr[0])."' , '".addslashes($rowAddr[1])."' , '".addslashes($invCustDet[12])."');";
		mysql_query($insInvoice_billTo_Qry,$db);
	}
}

//Saving BillTo for Invoice-BillingContact-ASGN
function saveInvoiceBillTo_billcont($client,$invId,$getAlertForMultipleInvoice,$chkAsgnments,$billaddr,$billcontuser)
{		
	global $username, $maildb,$db;

	$sqlClientDet = "SELECT staffacc_cinfo.sno,staffacc_cinfo.cname, staffacc_cinfo.address1, staffacc_cinfo.address2, staffacc_cinfo.city, staffacc_cinfo.state, countries.country,staffacc_cinfo.zip,staffacc_cinfo.phone,staffacc_cinfo.fax,staffacc_cinfo.bill_contact,staffacc_cinfo.bill_address,staffacc_cinfo.attention FROM staffacc_cinfo LEFT JOIN countries ON countries.sno = staffacc_cinfo.country WHERE staffacc_cinfo.sno='".$client."'";
	$resClientDet=mysql_query($sqlClientDet,$db);
	$invCustDet=mysql_fetch_row($resClientDet);
	
	/////////// Adding group by cond//
	$groupByCond = getGroupByCondForInvoiceBillTo($Client);			
	//////////////////////////////////
	$invCustDet[10] = $billcontuser;
	$invCustDet[11] = $billaddr;
	
	if($chkAsgnments != '')
	{	
		$getAssignmentAddress = "SELECT bill_contact, bill_address, attention  FROM hrcon_jobs WHERE sno IN (".$chkAsgnments.") GROUP BY ".$groupByCond;
		$resAssignmentAddress = mysql_query($getAssignmentAddress,$db);
		$rowAssignmentAddress = mysql_fetch_array($resAssignmentAddress);
		
		if($rowAssignmentAddress[1] != '0' && $rowAssignmentAddress[1] != '')
		{
			$invCustDet[10] = $rowAssignmentAddress[0];
			$invCustDet[11] = $rowAssignmentAddress[1];
		}

		if($rowAssignmentAddress[2] != '')
			$invCustDet[12] = $rowAssignmentAddress[2];
	}
	//Getting Billing Contact details
	$sqlBilCont="select CONCAT_WS(' ',fname,mname,lname),wphone from staffacc_contact where sno='".$invCustDet[10]."' ";
	$resBilCont=mysql_query($sqlBilCont,$db);
	$invBilCont=mysql_fetch_row($resBilCont);

	//Getting Billing contact  Addess details
	$sqlBilAddr="SELECT staffacc_location.address1,staffacc_location.address2,staffacc_location.city,staffacc_location.state,staffacc_location.zipcode,staffacc_location.csno,staffacc_location.ltype,countries.country,staffacc_location.title from staffacc_location LEFT JOIN countries ON countries.sno=staffacc_location.country WHERE staffacc_location.sno='".$invCustDet[11]."'";
	$resBilAddr=mysql_query($sqlBilAddr,$db);
	$invBilAddr=mysql_fetch_row($resBilAddr);

	
	/*$sqlAddr="select staffacc_contact.wphone,staffacc_location.title from staffacc_contact LEFT JOIN staffacc_location ON staffacc_location.csno=staffacc_contact.sno where staffacc_contact.sno='".$invBilAddr[5]."'";
	$resAddr=mysql_query($sqlAddr,$db);
	$rowAddr=mysql_fetch_row($resAddr);*/
	

	$insInvoice_billTo = "INSERT INTO invoice_billto ( sno , invid , Company_Name , Company_Addr1 , Company_Addr2 , Company_City , Company_State , Company_Zip , Company_Country , Company_PH , Company_Fax , Billing_CT , Billing_Addr1 , Billing_Addr2 , Billing_City , Billing_State , Billing_Zip , Billing_Country , Billing_PH , Billing_Comp , Company_Attn ) VALUES ('' , '".$invId."' , '".$invCustDet[1]."' , '".$invCustDet[2]."' , '".$invCustDet[3]."' , '".$invCustDet[4]."' , '".$invCustDet[5]."' , '".$invCustDet[7]."' , '".$invCustDet[6]."' , '".$invCustDet[8]."' , '".$invCustDet[9]."' , '".$invBilCont[0]."' , '".$invBilAddr[0]."' , '".$invBilAddr[1]."' , '".$invBilAddr[2]."' , '".$invBilAddr[3]."' , '".$invBilAddr[4]."' , '".$invBilAddr[7]."' , '".$invBilCont[1]."' , '".$invBilAddr[8]."' , '".addslashes($invCustDet[12])."');";	
	mysql_query($insInvoice_billTo,$db);

	$errNo = mysql_errno($db);

	if($errNo != '0')
	{
		$insInvoice_billTo_Qry = "INSERT INTO invoice_billto ( sno , invid , Company_Name , Company_Addr1 , Company_Addr2 , Company_City , Company_State , Company_Zip , Company_Country , Company_PH , Company_Fax , Billing_CT , Billing_Addr1 , Billing_Addr2 , Billing_City , Billing_State , Billing_Zip , Billing_Country , Billing_PH , Billing_Comp , Company_Attn ) VALUES ('' , '".$invId."' , '".addslashes($invCustDet[1])."' , '".addslashes($invCustDet[2])."' , '".addslashes($invCustDet[3])."' , '".addslashes($invCustDet[4])."' , '".addslashes($invCustDet[5])."' , '".addslashes($invCustDet[7])."' , '".addslashes($invCustDet[6])."' , '".addslashes($invCustDet[8])."' , '".addslashes($invCustDet[9])."' , '".addslashes($invBilCont[0])."' , '".addslashes($invBilAddr[0])."' , '".addslashes($invBilAddr[1])."' , '".addslashes($invBilAddr[2])."' , '".addslashes($invBilAddr[3])."' , '".addslashes($invBilAddr[4])."' , '".addslashes($invBilAddr[7])."' , '".addslashes($rowAddr[0])."' , '".addslashes($rowAddr[1])."' , '".addslashes($invCustDet[12])."');";	
		mysql_query($insInvoice_billTo_Qry,$db);
	}
}

function getCRMCompanyHierarchy($companyID)
{
	global $maildb,$db, $username, $companyChildIds;
	$queryCompany = "SELECT sno FROM staffoppr_cinfo WHERE status = 'ER' AND crmcompany='Y' AND parent = ".$companyID;
	$resCompany = mysql_query($queryCompany, $db);
	if(mysql_num_rows($resCompany) > 0){
		while($rowCompany = mysql_fetch_assoc($resCompany)){
			$companyChildIds.= $rowCompany['sno'].',';
			getCRMCompanyHierarchy($rowCompany['sno']);
		}
	}
}

function getCustomerBillingDetails($customerSno,$companySno=0,$callFrom='Placement')
{
   global $maildb,$db,$username;


   if($callFrom == 'Placement')
   {
		$getCrmCompDetails = "SELECT cinfo.bill_contact,cinfo.bill_address,cinfo.acc_comp,loc.csno,loc.ltype,cinfo.sno FROM staffoppr_cinfo cinfo LEFT JOIN staffoppr_location loc ON (cinfo.bill_address = loc.sno AND loc.status='A') WHERE cinfo.sno = '".$companySno."'";
		$resCrmCompDetails = mysql_query($getCrmCompDetails,$db);
		$rowCrmCompDetails = mysql_fetch_row($resCrmCompDetails);
		$customerSno = $rowCrmCompDetails[2];
		$attention = "";

		$crmBillComp = $rowCrmCompDetails[1];
		$crmBillCont = $rowCrmCompDetails[0];

		if($rowCrmCompDetails[4] == 'con') {
			$getCrmContDetails = "SELECT csno FROM staffoppr_contact WHERE sno = '".$rowCrmCompDetails[3]."'";
			$resCrmContDetails = mysql_query($getCrmContDetails,$db);
			$rowCrmContDetails = mysql_fetch_row($resCrmContDetails);

			$crmBillLoc = $rowCrmContDetails[0];
		}
		else
			$crmBillLoc = $rowCrmCompDetails[3];

   }
   else
   {
       if(!empty($companySno))
       {
			$getCrmCompDetails = "SELECT cinfo.bill_contact,cinfo.bill_address,cinfo.acc_comp,loc.csno,loc.ltype,cinfo.sno FROM staffoppr_cinfo cinfo LEFT JOIN staffoppr_location loc ON (cinfo.bill_address = loc.sno AND loc.status='A') WHERE cinfo.sno = '".$companySno."'";
			$resCrmCompDetails = mysql_query($getCrmCompDetails,$db);
			$rowCrmCompDetails = mysql_fetch_row($resCrmCompDetails);
			$customerSno = $rowCrmCompDetails[2];
			$attention = "";
			$crmBillComp = $rowCrmCompDetails[1];
			$crmBillCont = $rowCrmCompDetails[0];

			if($rowCrmCompDetails[4] == 'con') {
				$getCrmContDetails = "SELECT csno FROM staffoppr_contact WHERE sno = '".$rowCrmCompDetails[3]."'";
				$resCrmContDetails = mysql_query($getCrmContDetails,$db);
				$rowCrmContDetails = mysql_fetch_row($resCrmContDetails);

				$crmBillLoc = $rowCrmContDetails[0];
			}
			else
				$crmBillLoc = $rowCrmCompDetails[3];
       }

	   $getAccCustBillAddr = "SELECT cinfo.bill_contact,cinfo.bill_address,cinfo.attention,cinfo.username,loc.csno,loc.ltype,cinfo.sno FROM staffacc_cinfo cinfo LEFT JOIN staffacc_location loc ON (cinfo.bill_address = loc.sno AND loc.status='A') WHERE cinfo.sno = '".$customerSno."'";
       $resAccCustBillAddr=mysql_query($getAccCustBillAddr,$db);
       $rowAccCustBillAddr=mysql_fetch_row($resAccCustBillAddr);

       $custUsername = $rowAccCustBillAddr[3];
       $attention = $rowAccCustBillAddr[2];

       if($callFrom=='Placement')
       {
	       if($rowAccCustBillAddr[1]!='0')
	       {
		       $lque="SELECT al.sno, al.ltype, al.crm_loc FROM staffacc_location al WHERE al.sno='".$rowAccCustBillAddr[1]."'";
		       $lres=mysql_query($lque,$db);
		       $lrow=mysql_fetch_row($lres);
		       if($lrow[2]>0)
		       {
			       $crmBillComp=$lrow[2];
		       }
		       else
		       {
			       if($lrow[1]=="com")
				       $uque="UPDATE staffacc_location al, staffoppr_location ol, staffoppr_cinfo oc, staffacc_cinfo ac SET al.crm_loc = ol.sno WHERE ol.ltype='com' AND ol.csno=oc.sno AND ac.crm_comp = oc.sno AND al.ltype='com' AND al.csno=ac.sno AND al.sno=".$lrow[0];
			       else if($lrow[1]=="con")
				       $uque="UPDATE staffacc_location al, staffoppr_location ol, staffoppr_contact oc, staffacc_contact ac SET al.crm_loc = ol.sno WHERE ol.ltype='con' AND ol.csno=oc.sno AND ac.crm_cont = oc.sno AND al.ltype='con' AND al.csno=ac.sno AND al.sno=".$lrow[0];
			       mysql_query($uque,$db);

			       $que="SELECT crm_loc FROM staffacc_location WHERE sno=".$lrow[0];
			       $res=mysql_query($que,$db);
			       $row=mysql_fetch_row($res);
			       $crmBillComp=$row[0];
		       }
	       }

	       if($rowAccCustBillAddr[0]!='0')
	       {
		       $chkCrmCont = "SELECT crm_cont FROM staffacc_contact WHERE sno = '".$rowAccCustBillAddr[0]."'";
		       $resCrmCont = mysql_query($chkCrmCont,$db);
		       $rowCrmCont = mysql_fetch_row($resCrmCont);
		       $crmBillCont = $rowCrmCont[0];
	       }
       }
       else
       {
			$crmBillComp = $rowAccCustBillAddr[1];
			$crmBillCont = $rowAccCustBillAddr[0];

			if($rowAccCustBillAddr[5] == 'con') {
				$getAccContDetails = "SELECT csno FROM staffacc_contact WHERE sno = '".$rowAccCustBillAddr[4]."'";
				$resAccContDetails = mysql_query($getAccContDetails,$db);
				$rowAccContDetails = mysql_fetch_row($resAccContDetails);

				$crmBillLoc = $rowAccContDetails[0];
			}
			else
				$crmBillLoc = $rowAccCustBillAddr[4];
       }
   }

   if($callFrom == 'Placement')
	   $getCrmBillContDetails = "SELECT sno,CONCAT_WS(' ', staffoppr_contact.fname,staffoppr_contact.mname,staffoppr_contact.lname) FROM staffoppr_contact WHERE staffoppr_contact.sno='".$crmBillCont."'";
   else
	   $getCrmBillContDetails = "SELECT sno,CONCAT_WS(' ', staffacc_contact.fname, staffacc_contact.mname,staffacc_contact.lname) FROM staffacc_contact WHERE staffacc_contact.sno='".$crmBillCont."'";

   $resCrmBillContDetails = mysql_query($getCrmBillContDetails,$db);
   $rowCrmBillContDetails = mysql_fetch_row($resCrmBillContDetails);

   if($callFrom=='Placement')
	   $BillingInfoDetails = "^^Akkensplit^^".$crmBillLoc."^^Akkensplit^^".$crmBillComp."^^Akkensplit^^".$crmBillCont."^^Akkensplit^^".$rowCrmBillContDetails[1]."^^Akkensplit^^".$attention;
   else
	   $BillingInfoDetails = $custUsername."^^Akkensplit^^".$crmBillLoc."^^Akkensplit^^".$crmBillComp."^^Akkensplit^^".$crmBillCont."^^Akkensplit^^".$rowCrmBillContDetails[1]."^^Akkensplit^^".$attention;

   return $BillingInfoDetails;
}

function getGroupByCondForInvoiceBillTo($Client)
{
	global $maildb,$db;

	$getCustDetails = "SELECT templateid FROM staffacc_cinfo WHERE sno = '".$Client."'";
	$resCustDetails = mysql_query($getCustDetails,$db);
	$rowCustDetails = mysql_fetch_array($resCustDetails);

	$tpl_array_values = genericTemplate($rowCustDetails['templateid']);
	$template_Bill_To = $tpl_array_values[2];

	if($template_Bill_To['Billing_Add'][0] == 'Y')
		$billingAddressSelected = true;
	else
		$billingAddressSelected = false;

	if($billingAddressSelected)
	{
		if($template_Bill_To['Company_Attn'][0] == 'Y')
			$billingAttnSelected = true;
		else
			$billingAttnSelected = false;

		if($template_Bill_To['Billing_CT'][0] == 'Y')
			$billingContactSelected = true;
		else
			$billingContactSelected = false;
	}
	else
	{
		$billingAttnSelected = false;
		$billingContactSelected = false;
	}

	$groupByCond = "hrcon_jobs.bill_address";

	if($billingContactSelected)
		$groupByCond .= ", hrcon_jobs.bill_contact";

	if($billingAttnSelected)
		$groupByCond .= ", hrcon_jobs.attention";

	return $groupByCond;
}

//This function return owner department, if owner department is not found then returns default department.
function getOwnerDepartment()
{
	global $maildb,$db, $username;

	$queryDept = "SELECT dept FROM hrcon_compen WHERE ustatus = 'active' AND username = '".$username."'";
	$resDept = mysql_query($queryDept, $db);
	$rowDept = mysql_fetch_assoc($resDept);
	if($rowDept['dept'] != '' && $rowDept['dept'] != 0){
		return $rowDept['dept'];
	}else{
		$queryDept = "SELECT sno FROM department WHERE deflt = 'Y'";
		$resDept = mysql_query($queryDept, $db);
		$rowDept = mysql_fetch_assoc($resDept);
		return $rowDept['sno'];
	}

}
// Getting default department id.
function getDefaultHRMDepartment()
{
	global $maildb,$db, $username;
	$queryDept = "SELECT sno FROM department WHERE deflt = 'Y'";
	$resDept = mysql_query($queryDept, $db);
	$rowDept = mysql_fetch_assoc($resDept);
	if($rowDept['sno'] != '')
		return $rowDept['sno'];
	else
		return 1;

}

//Function to get State/Abbr from ID
function getStateAbbrFromId($stateId ,$dbcon=''){
	global $rptdb ,$db;
	$stateNmAb='|';
	$sel_stateQry = "SELECT state_abbr,state_name, state_id FROM state_codes WHERE state_id =".$stateId;
        if($dbcon == '') {
            $sel_stateRes = mysql_query($sel_stateQry,$rptdb);
        }else {
            $sel_stateRes = mysql_query($sel_stateQry,$db);
        }
	$sel_stateRow = mysql_fetch_row($sel_stateRes);
	$stateNmAb = $sel_stateRow[0].'|'.$sel_stateRow[1];
	return $stateNmAb;
}

function getRoleOverwriteFlag($roleSno)
{
	global $maildb,$db, $username;
	$getOverwriteFlag = "SELECT overwrite FROM company_commission WHERE sno = '".$roleSno."'";
	$resOverwriteFlag = mysql_query($getOverwriteFlag,$db);
	$rowOverwriteFlag = mysql_fetch_array($resOverwriteFlag);

	return $rowOverwriteFlag[0];
}

//Save path for dashboard report generated files
function getDashboardReportsFileSavePath($rptFolder)
{
	global $WDOCUMENT_ROOT;

	$pos = strrpos($WDOCUMENT_ROOT,'/');
	$rpath = substr($WDOCUMENT_ROOT,0,$pos);
	$rpath = $rpath.'/'.md5(uniqid(mt_rand(), true));

	if(!is_dir($rpath))
		mkdir($rpath,0770);

	$reportsDir=realpath($rpath."/");

	if(is_dir($reportsDir))
	{
		if(!is_dir($reportsDir."/reports/"))
		{
			$Ddh=mkdir($reportsDir."/reports/");
			if(!is_dir($reportsDir."/reports/".$rptFolder."/"))
			{
				$dHandler= mkdir($reportsDir."/reports/".$rptFolder."/");
				if($dHandler)
					return $reportsDir."/reports/".$rptFolder."/";
				else
					return 'error';
			}
			else
			{
				return $reportsDir."/reports/".$rptFolder."/";
			}
		}
		else
		{
			if(!is_dir($reportsDir."/reports/".$rptFolder."/"))
			{
				$dHandler= mkdir($reportsDir."/reports/".$rptFolder."/");
				if($dHandler)
					return $reportsDir."/reports/".$rptFolder."/";
				else
					return 'error';
			}
			else
			{
				return $reportsDir."/reports/".$rptFolder."/";
			}
		}
	}
	else
	{
		return 'error1';
	}
}

function checkOBAssignments($status)
{
	global $maildb,$db,$conusername,$username;

	$todayDate = date("m-d-Y");

	if($status=="active")
	{
		$ActiveQry = "select count(1) from hrcon_jobs where jtype='OP' AND ustatus = 'active' and username = '".$conusername."'";
		$ResActive = mysql_query($ActiveQry);
		$RowActive = mysql_fetch_array($ResActive);
		if($RowActive[0] == 0)
		{
			$edque="SELECT DATE_ADD(MAX(DATE_FORMAT(STR_TO_DATE(e_date,'%m-%d-%Y'),'%m-%d-%Y')),INTERVAL 1 DAY) FROM hrcon_jobs WHERE username = '$conusername' AND jtype='OP' GROUP BY username";
			$edres=mysql_query($edque,$db);
			$edrow=mysql_fetch_row($edres);

			if($edrow[0]=="" || $edrow[0]=="0-0-0")
				$start_date=$todayDate;
			else
				$start_date=$edrow[0];

			$start_date=$todayDate;
			$assg_pusername_ob=get_Assginment_Seq();

			$OBQry = "select sno from hrcon_jobs where jtype='OB' AND username = '".$conusername."'";
			$OBRes = mysql_query($OBQry,$db);
			$OBRow = mysql_fetch_row($OBRes);
			if(mysql_num_rows($OBRes) == 0)
			{
				$InsQry =  "insert into hrcon_jobs (sno,username,jtype,assign_no,ustatus,udate,owner,muser,mdate,cdate,pusername) values ('','".$conusername."', 'OB','".$assg_pusername_ob."','active', now(),'".$username."','".$username."',NOW(),NOW(),'".$assg_pusername_ob."')";
				$ResIns = mysql_query($InsQry,$db);
				$hrsno=mysql_insert_id($db);

				$ccid = $hrsno."|";
				insertDefaultOBPayBillRates($hrsno);
				$assignment_array=array("modulename" => "HR->Assignments", "pusername" => "$assg_pusername_ob", "userid" => $conusername, "contactsno" => $ccid, "invapproved" => 'active');
				$ResIns=insertAssignmentSchedule($assignment_array);
			}
			else
			{
				$hrsno = $OBRow[0];
				$que="UPDATE hrcon_jobs SET ustatus='active',muser='$username',mdate=NOW(),date_ended=NOW(),s_date='$start_date',e_date='0-0-0' WHERE sno=$hrsno";
				mysql_query($que,$db);
			}
		}
		else
		{
			$sel_hrsno="SELECT sno FROM hrcon_jobs WHERE username='".$conusername."' AND ustatus='active' AND jtype='OB'";
			$res_hrsno=mysql_query($sel_hrsno,$db);
			while($fetch_hrsno=mysql_fetch_row($res_hrsno))
			{
				$que="UPDATE hrcon_jobs SET ustatus='closed',s_date='0-0-0',e_date='0-0-0',muser='".$username."',mdate=NOW(),date_ended=NOW() WHERE sno='".$fetch_hrsno[0]."'";
				mysql_query($que,$db);
			}
		}
	}
	else
	{
		$sel_hrsno="SELECT sno FROM hrcon_jobs WHERE username='".$conusername."' AND ustatus='active' AND jtype='OB'";
		$res_hrsno=mysql_query($sel_hrsno,$db);
		while($fetch_hrsno=mysql_fetch_row($res_hrsno))
		{
			$que="UPDATE hrcon_jobs SET ustatus='closed',s_date='0-0-0',e_date='0-0-0',muser='".$username."',mdate=NOW(),date_ended=NOW() WHERE sno='".$fetch_hrsno[0]."'";
			mysql_query($que,$db);
		}
	}
}

/*
function NEW_checkOBAssignments($status)
{
	global $maildb,$db,$conusername,$username;

	$start_date=date("m-d-Y");

	if($status=="active")
	{
		$OBQry = "select sno from hrcon_jobs where jtype='OB' AND username = '".$conusername."'";
		$OBRes = mysql_query($OBQry,$db);
		$OBRow = mysql_fetch_row($OBRes);
		if(mysql_num_rows($OBRes) == 0)
		{
			$assg_pusername_ob=get_Assginment_Seq();

			$InsQry =  "insert into hrcon_jobs (sno,username,jtype,assign_no,ustatus,udate,owner,muser,mdate,cdate,pusername) values ('','".$conusername."', 'OB','".$assg_pusername_ob."','active', now(),'".$username."','".$username."',NOW(),NOW(),'".$assg_pusername_ob."')";
			$ResIns = mysql_query($InsQry,$db);
			$hrsno=mysql_insert_id($db);

			$ccid = $hrsno."|";
			insertDefaultOBPayBillRates($hrsno);
			$assignment_array=array("modulename" => "HR->Assignments", "pusername" => "$assg_pusername_ob", "userid" => $conusername, "contactsno" => $ccid, "invapproved" => 'active');
			$ResIns=insertAssignmentSchedule($assignment_array);
		}
		else
		{
			$hrsno = $OBRow[0];
			$que="UPDATE hrcon_jobs SET ustatus='active',muser='$username',mdate=NOW(),date_ended=NOW(),s_date='$start_date',e_date='0-0-0' WHERE sno=$hrsno";
			mysql_query($que,$db);
		}
	}
	else
	{
		$sel_hrsno="SELECT sno FROM hrcon_jobs WHERE username='".$conusername."' AND jtype='OB'";
		$res_hrsno=mysql_query($sel_hrsno,$db);
		while($fetch_hrsno=mysql_fetch_row($res_hrsno))
		{
			$que="UPDATE hrcon_jobs SET ustatus='closed',s_date='0-0-0',e_date='0-0-0',muser='".$username."',mdate=NOW(),date_ended=NOW() WHERE sno='".$fetch_hrsno[0]."'";
			mysql_query($que,$db);
		}
	}
}
*/

function insertDefaultOBPayBillRates($hrsno)
{
	global $maildb,$db,$username;

	$ratesStr =  "|||Y|N|payrate|Regular|^||||Y|N|billrate|Regular|^||||Y|N|payrate|OverTime|^||||Y|N|billrate|OverTime|^||||Y|N|payrate|DoubleTime|^||||Y|N|billrate|DoubleTime";
	$exp_rates = explode("|^|",$ratesStr);
	$count_rates=count($exp_rates);

	$rate_ids_arr = array('Regular' => "rate1" , 'OverTime' => "rate2", 'DoubleTime' => "rate3");
	for($i=0;$i<$count_rates;$i++)
	{
		$exp_pay_bill_rates =  explode("|",$exp_rates[$i]);

		$value_set = "('".$hrsno."','hrcon','".$rate_ids_arr[$exp_pay_bill_rates[6]]."','".$exp_pay_bill_rates[5]."','".$exp_pay_bill_rates[0]."','".$exp_pay_bill_rates[1]."','".$exp_pay_bill_rates[2]."','".$exp_pay_bill_rates[3]."','".$exp_pay_bill_rates[4]."','ACTIVE','','".$username."',NOW(),'".$username."',NOW())";
		$ins_rates = "INSERT INTO multiplerates_assignment (asgnid,asgn_mode,ratemasterid,ratetype,rate,period,currency,billable,taxable,status,parent_update, cuser,cdate,muser,mdate)  VALUES ".$value_set;
		mysql_query($ins_rates,$db);
	}
}

function closeDirectJobAssignments($enddate)
{
	global $maildb,$db,$conusername,$username,$emp_sno_val;

	$concatVar="";
	$appno_tab="";

	$hrcon_str="";


	$sel_hrcon="SELECT sno FROM hrcon_jobs WHERE ustatus='active' AND username='".$conusername."' AND jtype!=''";
	$res_hrcon=mysql_query($sel_hrcon,$db);
	while($fetch_hrcon=mysql_fetch_row($res_hrcon))
	{

		$sel_tab="SELECT appno, contactsno FROM assignment_schedule WHERE userid='".$conusername."' AND modulename='HR->Assignments' AND contactsno like'".$fetch_hrcon[0]."|%' AND invapproved='active'";
		$res_tab=mysql_query($sel_tab,$db);

		$fetch_tab=mysql_fetch_row($res_tab);
		
		$appno_tab.=$concatVar.$fetch_tab[0];

		$hrcon_str.=$concatVar.$fetch_hrcon[0];

		$concatVar=",";
	}

	if($hrcon_str!="")
	{
		if($enddate=="")
			$todayDate = date("m-d-Y");
		else
			$todayDate = $enddate;

		$que="UPDATE hrcon_jobs SET ustatus='closed',muser='$username',mdate=NOW(),date_ended=NOW(),e_date = IF(e_date='0-0-0' || e_date='','".$todayDate."',e_date) WHERE username='".$conusername."' AND sno IN (".$hrcon_str.")";
		mysql_query($que,$db);

		$pque="SELECT posid, candidate, assign_no,e_date,sno,schedule_display,shift_type FROM hrcon_jobs WHERE sno IN (".$hrcon_str.")";
		$pres=mysql_query($pque,$db);
		while($prow=mysql_fetch_row($pres))
		{
			$posid=$prow[0];
			$candidateID=$prow[1];
			$cand_username="cand".$prow[1];
			$assignmentNo=$prow[2];
			$TFJobPid = $prow[4];
			$tmpEDate = explode("-",$prow[3]);
			$asg_e_date = $tmpEDate[2]."-".sprintf("%02d",$tmpEDate[0])."-".sprintf("%02d",$tmpEDate[1]);//get the end date from the hrcon jobs table
			$assignment_e_date = date("Y-m-d",strtotime($asg_e_date));

			//Making the employee slots to available and updating the employee shifts to candidate
			if($TFJobPid != "" && $prow[5] == 'NEW')
			{
				require_once('shift_schedule/hrm_schedule_db.php');
				$objScheduleDetails	= new EmployeeSchedule();

				/* including shift schedule class file for converting Minutes to DateTime / DateTime to Minutes */
				require_once('shift_schedule/class.schedules.php');
				$objSchSchedules	= new Schedules();
				/*
					Perdiem Shift Scheduling Class file
				*/
				require_once('perdiem_shift_sch/Model/class.hrm_perdiem_sch_db.php');
				$hrmPerdiemShiftSchObj = new HRMPerdiemShift();
				//GETTIGN SM AVAILABILITY TIMNEFRAME DETAILS
				$previousDate = date("Y-m-d",strtotime("-2 months",strtotime(date("Y-m-d"))));
				if ($prow[6] == "perdiem") {
					$select = "SELECT hpss.shift_startdate AS startDate,hpss.shift_starttime AS startTime,
					hpss.shift_enddate AS endDate,hpss.shift_endtime AS endTime,hpss.split_shift AS splitShift,
					hpss.no_of_shift_position AS noOfShifts,hpss.shift_id AS shiftSno,ss.shiftname AS shiftName,
					ss.shiftcolor AS shiftColor
			 		FROM hrconjob_perdiem_shift_sch hpss 
			 		LEFT JOIN shift_setup ss ON (ss.sno = hpss.shift_id)
			 		WHERE hpss.pusername='".$assignmentNo."' AND hpss.shift_startdate >= '".$assignment_e_date."' 
			 		ORDER BY hpss.shift_startdate ASC ";
					$resultSel = mysql_query($select,$db);
					$hrmPerdiemShiftSchObj->oldShiftStrAry = array();
					while ($rowSel = mysql_fetch_array($resultSel)) {
						$hrmPerdiemShiftSchObj->prepareOldShiftStrFromPerdiemSelQry($rowSel,$conusername);
					}

					$timeslotvalues = implode("|", $hrmPerdiemShiftSchObj->oldShiftStrAry);
				}else{
					$get_assgn_tf_sql 	= "SELECT	DATE_FORMAT(shift_date,'%m/%d/%Y'),
											shift_starttime,
											shift_endtime,
											event_no,
											event_group_no,
											shift_status,
											sm_sno,
											no_of_positions
									FROM  hrconjob_sm_timeslots
									WHERE pid = '".$TFJobPid."' AND shift_date >= '".$assignment_e_date."'
									ORDER BY shift_date ASC
									";

					$get_assgn_tf_res	= mysql_query($get_assgn_tf_sql, $db);
					if (mysql_num_rows($get_assgn_tf_res) > 0)
					{
						while ($row	= mysql_fetch_array($get_assgn_tf_res))
						{
							$seldateval = $row[0];

							//convert into minutes
							$fromTF 	= $objSchSchedules->getMinutesFrmDateTime($row[1]);
							$toTF 		= $objSchSchedules->getMinutesFrmDateTime($row[2]);
							$recNo		= $row[3];
							$slotGrpNo	= $row[4];
							$shiftStatus	= $row[5];
							$shiftNameSno	= $row[6];
							$shiftPosNum	= $row[7];

							$shiftSetupDetails = $objSchSchedules->getShiftNameColorBySno($shiftNameSno);
							list($shiftName,$shiftColor) = explode("|",$shiftSetupDetails);

							$timeslotvalues .= $seldateval."^".$fromTF."^".$toTF."^".$recNo."^".$slotGrpNo."^".$shiftStatus."^".$shiftName."^".$shiftColor."^".$shiftNumPos."|";
						}
						$timeslotvalues = substr($timeslotvalues,0,strlen($timeslotvalues)-1);
					}
				}
				
				$objScheduleDetails->updAvailableEmpTimeSlots($username, $conusername, $timeslotvalues, $assignmentNo, $assignment_e_date);

				//Dumping data into Candidate Shift Schedule from Employee Shift Schedule from the end date to future dates only.

				$objScheduleDetails->deleteNInsertDataIntoCandidateFromEmployeeScheduleTable($emp_sno_val, $conusername,$assignment_e_date);
			}

			if($posid!=0 && $posid)
			{
				$astatus = 'Closed';
				$managesno=getManageSno($astatus,'interviewstatus');

				$sel_place_jobs="SELECT sno, seqnumber FROM placement_jobs WHERE assign_no='".$assignmentNo."' AND username='".$conusername."'";
				$res_place_jobs=mysql_query($sel_place_jobs, $db);
				$fetch_place_jobs=mysql_fetch_array($res_place_jobs);

				$res_upd="UPDATE resume_status SET status='".$managesno."', muser = '".$username."', mdate = NOW() WHERE res_id='".$candidateID."' AND req_id='".$posid."' AND seqnumber='".$fetch_place_jobs['seqnumber']."'";
				mysql_query($res_upd,$db);

				$res_upd="UPDATE placement_jobs SET assg_status='".$astatus."' WHERE username='".$conusername."' and posid='".$posid."' AND candidate = '".$cand_username."' AND sno='".$fetch_place_jobs['sno']."'";
				mysql_query($res_upd,$db);
			}
		}


		/*$previousDate = date("Y-m-d",strtotime("-1 days",strtotime(date("Y-m-d"))));
		$up_emp_tf_sql = "UPDATE hrcon_sm_timeslots SET shift_status='available',mtime=NOW(),muser='".$username."',placed_assgno='' WHERE username = '".$conusername."' AND shift_date >= '".$previousDate."' AND shift_status='busy'";
		$up_emp_tf_res = mysql_query($up_emp_tf_sql,$db);*/

		$updassignment_array=array("invapproved" => "closed", "appno" => "appno IN (".$appno_tab.")", "userid" => "", "modulename" => "", "contactsno" => "");
		updateAssignmentSchedule($updassignment_array);
	}
}


function closeTerminatedEmployeeAssignments($enddate,$emp_sno_val)
{
	global $maildb,$db,$username,$conusername;

	$concatVar="";
	$appno_tab="";
	$hrcon_str="";

	$sel_hrcon="SELECT sno FROM hrcon_jobs WHERE ustatus='active' AND username='".$conusername."' AND jtype!=''";
	$res_hrcon=mysql_query($sel_hrcon,$db);
	while($fetch_hrcon=mysql_fetch_row($res_hrcon))
	{
		
		$sel_tab="SELECT appno FROM assignment_schedule WHERE userid='".$conusername."' AND modulename='HR->Assignments' AND contactsno like'".$fetch_hrcon[0]."|%' AND invapproved='active'";
		$res_tab=mysql_query($sel_tab,$db);

		$fetch_tab=mysql_fetch_row($res_tab);
		$appno_tab.=$concatVar.$fetch_tab[0];



		$hrcon_str.=$concatVar.$fetch_hrcon[0];

		$concatVar=",";
	}

	if($hrcon_str!="")
	{
		if($enddate=="")
			$todayDate = date("m-d-Y");
		else
			$todayDate = $enddate;

		$que="UPDATE hrcon_jobs SET ustatus='closed',muser='$username',mdate=NOW(),date_ended=NOW(),e_date = IF(e_date='0-0-0' || e_date='','".$todayDate."',e_date) WHERE username='".$conusername."' AND sno IN (".$hrcon_str.")";
		mysql_query($que,$db);

		$pque="SELECT posid, candidate, assign_no,e_date,sno,schedule_display,shift_type FROM hrcon_jobs WHERE sno IN (".$hrcon_str.")";
		$pres=mysql_query($pque,$db);
		while($prow=mysql_fetch_row($pres))
		{
			$posid=$prow[0];
			$candidateID=$prow[1];
			$cand_username="cand".$prow[1];
			$assignmentNo=$prow[2];
			$TFJobPid = $prow[4];
			$tmpEDate = explode("-",$prow[3]);
			$asg_e_date = $tmpEDate[2]."-".sprintf("%02d",$tmpEDate[0])."-".sprintf("%02d",$tmpEDate[1]);//get the end date from the hrcon jobs table
			$assignment_e_date = date("Y-m-d",strtotime($asg_e_date));

			//Making the employee slots to available and updating the employee shifts to candidate
			if($TFJobPid != "" && $prow[5] == 'NEW')
			{
				require_once('shift_schedule/hrm_schedule_db.php');
				$objScheduleDetails	= new EmployeeSchedule();

				/* including shift schedule class file for converting Minutes to DateTime / DateTime to Minutes */
				require_once('shift_schedule/class.schedules.php');
				$objSchSchedules	= new Schedules();

				/*
					Perdiem Shift Scheduling Class file
				*/
				require_once('perdiem_shift_sch/Model/class.hrm_perdiem_sch_db.php');
				$hrmPerdiemShiftSchObj = new HRMPerdiemShift();
				//GETTIGN SM AVAILABILITY TIMNEFRAME DETAILS
				$previousDate = date("Y-m-d",strtotime("-2 months",strtotime(date("Y-m-d"))));
				if ($prow[6] == "perdiem") {
					$select = "SELECT hpss.shift_startdate AS startDate,hpss.shift_starttime AS startTime,
					hpss.shift_enddate AS endDate,hpss.shift_endtime AS endTime,hpss.split_shift AS splitShift,
					hpss.no_of_shift_position AS noOfShifts,hpss.shift_id AS shiftSno,ss.shiftname AS shiftName,
					ss.shiftcolor AS shiftColor
			 		FROM hrconjob_perdiem_shift_sch hpss 
			 		LEFT JOIN shift_setup ss ON (ss.sno = hpss.shift_id)
			 		WHERE hpss.pusername='".$assignmentNo."' AND hpss.shift_startdate >= '".$assignment_e_date."' 
			 		ORDER BY hpss.shift_startdate ASC ";
					$resultSel = mysql_query($select,$db);
					$hrmPerdiemShiftSchObj->oldShiftStrAry = array();
					while ($rowSel = mysql_fetch_array($resultSel)) {
						$hrmPerdiemShiftSchObj->prepareOldShiftStrFromPerdiemSelQry($rowSel,$conusername);
					}

					$timeslotvalues = implode("|", $hrmPerdiemShiftSchObj->oldShiftStrAry);
				}else{
					$get_assgn_tf_sql 	= "SELECT	DATE_FORMAT(shift_date,'%m/%d/%Y'),
												shift_starttime,
												shift_endtime,
												event_no,
												event_group_no,
												shift_status,
												sm_sno,
												no_of_positions
										FROM  hrconjob_sm_timeslots
										WHERE pid = '".$TFJobPid."' AND shift_date >= '".$assignment_e_date."'
										ORDER BY shift_date ASC
										";

					$get_assgn_tf_res	= mysql_query($get_assgn_tf_sql, $db);
					if (mysql_num_rows($get_assgn_tf_res) > 0)
					{
						while ($row	= mysql_fetch_array($get_assgn_tf_res))
						{
							$seldateval = $row[0];

							//convert into minutes
							$fromTF 	= $objSchSchedules->getMinutesFrmDateTime($row[1]);
							$toTF 		= $objSchSchedules->getMinutesFrmDateTime($row[2]);
							$recNo		= $row[3];
							$slotGrpNo	= $row[4];
							$shiftStatus	= $row[5];
							$shiftNameSno	= $row[6];
							$shiftPosNum	= $row[7];

							$shiftSetupDetails = $objSchSchedules->getShiftNameColorBySno($shiftNameSno);
							list($shiftName,$shiftColor) = explode("|",$shiftSetupDetails);

							$timeslotvalues .= $seldateval."^".$fromTF."^".$toTF."^".$recNo."^".$slotGrpNo."^".$shiftStatus."^".$shiftName."^".$shiftColor."^".$shiftNumPos."|";
						}
						$timeslotvalues = substr($timeslotvalues,0,strlen($timeslotvalues)-1);
					}
				}
				$objScheduleDetails->updAvailableEmpTimeSlots($username, $conusername, $timeslotvalues, $assignmentNo, $assignment_e_date);

				//Dumping data into Candidate Shift Schedule from Employee Shift Schedule from the end date to future dates only.

				$objScheduleDetails->deleteNInsertDataIntoCandidateFromEmployeeScheduleTable($emp_sno_val, $conusername,$assignment_e_date);
			}

			if($posid!=0 && $posid)
			{
				$astatus = 'Closed';
				$managesno=getManageSno($astatus,'interviewstatus');

				$sel_place_jobs="SELECT sno, seqnumber FROM placement_jobs WHERE assign_no='".$assignmentNo."' AND username='".$conusername."'";
				$res_place_jobs=mysql_query($sel_place_jobs, $db);
				$fetch_place_jobs=mysql_fetch_array($res_place_jobs);

				$res_upd="UPDATE resume_status SET status='".$managesno."', muser = '".$username."', mdate = NOW() WHERE res_id='".$candidateID."' AND req_id='".$posid."' AND seqnumber='".$fetch_place_jobs['seqnumber']."'";
				mysql_query($res_upd,$db);

				$res_upd="UPDATE placement_jobs SET assg_status='".$astatus."' WHERE username='".$conusername."' and posid='".$posid."' AND candidate = '".$cand_username."' AND sno='".$fetch_place_jobs['sno']."'";
				mysql_query($res_upd,$db);
			}
		}


		/*$previousDate = date("Y-m-d",strtotime("-1 days",strtotime(date("Y-m-d"))));
		$up_emp_tf_sql = "UPDATE hrcon_sm_timeslots SET shift_status='available',mtime=NOW(),muser='".$username."',placed_assgno='' WHERE username = '".$conusername."' AND shift_date >= '".$previousDate."'  AND shift_status='busy' ";
		$up_emp_tf_res = mysql_query($up_emp_tf_sql,$db);*/

		$updassignment_array=array("invapproved" => "closed", "appno" => "appno IN (".$appno_tab.")", "userid" => "", "modulename" => "", "contactsno" => "");
		updateAssignmentSchedule($updassignment_array);
	}
}

function getMultiLocations($locations)
{
	global $db;

	$sloc=explode(",",$locations);

	$ms_locations="<select style='width: 250px;' id=ms_locations multiple=multiple>";
	$ique="SELECT serial_no, CONCAT_WS(' - ',loccode,heading) FROM contact_manage WHERE status!='BP' ORDER BY loccode";
	$ires=mysql_query($ique,$db);
	while($irow=mysql_fetch_row($ires))
	{
		if(in_array($irow[0],$sloc))
			$ms_locations.="<option selected value='".$irow[0]."'>".$irow[1]."</option>";
		else
			$ms_locations.="<option value='".$irow[0]."'>".$irow[1]."</option>";
	}
	$ms_locations.="</select>";

	return $ms_locations;
}


	function buildProfileTitle($restitle,$eStr,$joborderid)
	{
		global $db;

		$proftitle=$restitle;

		if($joborderid>0 && $joborderid!="")
		{
			$que="SELECT postitle FROM api_jobs WHERE sno='".$joborderid."'";
			$res=mysql_query($que,$db);
			$row=mysql_fetch_row($res);
			$proftitle=$row[0];
		}
		else
		{
			$workExp=workExpSort($eStr);
			for($i=0;$i<count($workExp);$i++)
			{
				$seWork=explode("|",$workExp[$i]);
				if(trim($seWork[1])!="")
				{
					$proftitle=$seWork[1];
					break;
				}
			}
		}

		return $proftitle;
	}

	function workExpSort($expstr)
	{
		$Sort_Pre=array();
		$Sort_Emp=array();
		$Sort_Rem=array();
		$Sort_RemFin=array();
		$Exp_Sort1=array();
		$atest1=array();

		$Exp_Sort=explode("^",$expstr);

		$j=0;
		$k=0;
		$m=0;

		for($i=0;$i<count($Exp_Sort);$i++)
		{
			$Exp_Sort1[$i]=$Exp_Sort[$i];
			$Exp_Sort[$i]=explode("|",$Exp_Sort[$i]);

			if((trim($Exp_Sort[$i][0])=='Present') || (trim($Exp_Sort[$i][0])=='Present-0')||(trim($Exp_Sort[$i][0])=='-'))
			{
				$Sort_Pre[$j]=$Exp_Sort1[$i];
				$j++;
			}
			else if( (trim($Exp_Sort[$i][0])=='') || (trim($Exp_Sort[$i][0])=='0-0'))
			{
				$Sort_Emp[$k]=$Exp_Sort1[$i];
				$k++;
			}
			else
			{
				$Sort_Rem[$i]=$Exp_Sort1[$i];
				$atest=explode("-",$Exp_Sort[$i][0]);
				if((trim($atest[0])=="") || (trim($atest[0])=="0"))
				{
					$atest1[$i]=$atest[1]."13";
				}
				else
				{
					$h="";
					switch(strtolower($atest[0]))
					{
						case "january":$h="01";
						break;
						case "february":$h="02";
						break;
						case "march":$h="03";
						break;
						case "april":$h="04";
						break;
						case "may":$h="05";
						break;
						case "june":$h="06";
						break;
						case "july":$h="07";
						break;
						case "august":$h="08";
						break;
						case "september":$h="09";
						break;
						case "october":$h="10";
						break;
						case "november":$h="11";
						break;
						case "december":$h="12";
						break;
						default :$h="";
					}
					$atest1[$i]=$atest[1].$h;
				}
			}
		}

		arsort($atest1);

		foreach ($atest1 as $key => $val)
		{
			$Sort_RemFin[$key]=$Sort_Rem[$key];
		}

		$Sort_RemFinT=array_merge($Sort_Pre,$Sort_Emp,$Sort_RemFin);

		return $Sort_RemFinT;
	}

	//THIS function is used to get the burden type id and burden item ids from the formed string and return the ids
	function saveBurdenDetails($bt_str,$bi_str,$table,$table_id,$burden_action,$parent_id)
	{
                global $db;
		$burden_type_id = "";
		$burden_item_id = "";
		$bt_str_exp = explode("|",$bt_str);
		$burden_type_id = $bt_str_exp[0];

		$bi_str_exp = explode("|",$bi_str);
		foreach($bi_str_exp as $bi_inner_str)
		{
			$bi_inner_str_exp = explode("^",$bi_inner_str);
			$burden_item_id .= $bi_inner_str_exp[0].",";
		}
		$burden_item_id = substr($burden_item_id,0,strlen($burden_item_id)-1);

		//return $burden_type_id."|".$burden_item_id;

                $burden_item_ids = explode(",",$burden_item_id);
                if($burden_action == 'update')
                {
                    delBurdenDetails($table,$table_id,$parent_id);
                }
                for($init = 0;$init<count($burden_item_ids);$init++)
                {
                    if($burden_type_id != "" && $burden_item_ids[$init] != ""){
                        $posdesc_burden_details_ins = "INSERT INTO ".$table." SET ".$table_id." = '".$parent_id."', ratemasterid = 'rate1', ratetype = 'payrate', bt_id = '".$burden_type_id."', bi_id = '".$burden_item_ids[$init]."'";
                        mysql_query($posdesc_burden_details_ins, $db);
                        $update_bi_assign = "UPDATE burden_items SET assigned = 'yes' WHERE sno = '".$burden_item_ids[$init]."' AND assigned = 'no'";
                        mysql_query($update_bi_assign, $db);
                    }
                }

                if($burden_type_id != ""){
                    $update_bt_assign = "UPDATE burden_types SET assigned = 'yes' WHERE sno = '".$burden_type_id."' AND assigned = 'no'";
                    mysql_query($update_bt_assign, $db);
                }
	}

        function saveBillBurdenDetails($bt_str,$bi_str,$table,$table_id,$burden_action,$parent_id)
	{
                global $db;
		$burden_type_id = "";
		$burden_item_id = "";
		$bt_str_exp = explode("|",$bt_str);
		$burden_type_id = $bt_str_exp[0];

		$bi_str_exp = explode("|",$bi_str);
		foreach($bi_str_exp as $bi_inner_str)
		{
			$bi_inner_str_exp = explode("^",$bi_inner_str);
			$burden_item_id .= $bi_inner_str_exp[0].",";
		}
		$burden_item_id = substr($burden_item_id,0,strlen($burden_item_id)-1);

		//return $burden_type_id."|".$burden_item_id;

                $burden_item_ids = explode(",",$burden_item_id);
                for($init = 0;$init<count($burden_item_ids);$init++)
                {
                    if($burden_type_id != "" && $burden_item_ids[$init] != ""){
                        $posdesc_burden_details_ins = "INSERT INTO ".$table." SET ".$table_id." = '".$parent_id."', ratemasterid = 'rate1', ratetype = 'billrate', bt_id = '".$burden_type_id."', bi_id = '".$burden_item_ids[$init]."'";
                        mysql_query($posdesc_burden_details_ins, $db);
                        $update_bi_assign = "UPDATE burden_items SET assigned = 'yes' WHERE sno = '".$burden_item_ids[$init]."' AND assigned = 'no'";
                        mysql_query($update_bi_assign, $db);
                    }
                }

                if($burden_type_id != ""){
                    $update_bt_assign = "UPDATE burden_types SET assigned = 'yes' WHERE sno = '".$burden_type_id."' AND assigned = 'no'";
                    mysql_query($update_bt_assign, $db);
                }
	}

        function delBurdenDetails($table,$table_id,$parent_id)
        {
            global $db;
            $del_burden_details = "DELETE FROM ".$table." WHERE ".$table_id." = ".$parent_id."";
            mysql_query($del_burden_details, $db);
        }

	//This function is used to form the burden type and burden items string for displaying in the summary sections of all modules
	function buildBurdenDetails($bt_str,$bi_str)
	{
		$returnStr = "";
		$bt_str_exp = explode("|",$bt_str);
		$returnStr .= $bt_str_exp[1]." (";

		$bi_str_exp = explode("|",$bi_str);

		foreach($bi_str_exp as $bi_inner_str)
		{
			$bi_inner_str_exp = explode("^",$bi_inner_str);
			if($bi_inner_str_exp[0] == 1 || $bi_inner_str_exp[1] == 'Zero Bill Burden') // for zero burden item
			{
				$returnStr .= "0% +";
			}
			else
			{
				$bi_name = $bi_inner_str_exp[1];
				$bi_val = $bi_inner_str_exp[2];
				$bi_perc = $bi_inner_str_exp[3];
				$suf = "";
				if($bi_perc == 'percentage')
				{
					$suf = "%";
				}
				$returnStr .= $bi_name."-".$bi_val.$suf." + ";
			}
		}
		$returnStr = substr($returnStr,0,strlen($returnStr)-2);
		$returnStr .= ")";
		if($bt_str == "" || $bt_str == null)
		{
			$returnStr = "";
		}
		return $returnStr;
	}

        function getBurdenStatus() //To get burden management statu whether it is enabled or disabled.
        {
            global $db;
            $sts_sel = "SELECT status FROM burden_management";
            $sts_res = mysql_query($sts_sel, $db);
            $sts_rec = mysql_fetch_array($sts_res);

		return $sts_rec['status'];
	}

	/*
	 * To Maintain Log for an Employee in Employee Management >> Activities Tab, when Data is exported from
	 * CRM / Admin >> Contacts/Companies/Candidates Grids AND
	 * Analytics >> Contacts/CDF Contacts, Companies/CDF Companies, Candidates/CDF Candidates Reports [Export]
	 *
	 * @param	string $file_name, string $file_content, string $subject, string $subtype
	 * @return	boolean $ins_result_qry
	 */
	function logExportDataToActivities($file_name, $file_type, $file_content, $subject, $subtype) {

		global $db, $username;

		$res_log_qry	= FALSE;
		$employee_id	= getEmployeeIDFromUsername($username);
		$employee_id	= "emp".$employee_id;

		$ins_exp_qry	= "INSERT INTO
							contact_doc(con_id, username, title, docname, body, notes, sdate, doctype)
							VALUES
								('".$employee_id."', '".$username."', '".$subtype."', '".$file_name."', '".addslashes($file_content)."', 'Data Export', NOW(), '".$file_type."')";

		$res_exp_qry	= mysql_query($ins_exp_qry, $db);

		if ($res_exp_qry) {

			$tysno	= mysql_insert_id($db);

			$ins_log_qry	= "INSERT INTO
								cmngmt_pr(con_id, username, tysno, title, sdate, subject, lmuser, subtype)
								VALUES
									('".$employee_id."', '".$username."', $tysno, 'Data Export', NOW(), '".$subject."', '".$username."', '".$subtype."')";

			$res_log_qry	= mysql_query($ins_log_qry, $db);
		}

		return $res_log_qry;
	}

	/*
	 * Method used to get the Employee ID using Employee Username
	 *
	 * @param	string $empusername
	 * @return	integer $employee_id
	 */
	function getEmployeeIDFromUsername($empusername) {

		global $db;

		$employee_id	= 0;

		if ($empusername == "")
		return $employee_id;

		$query_string	= "SELECT sno FROM emp_list WHERE username='".$empusername."'";
		$query_result	= mysql_query($query_string, $db);

		if (mysql_num_rows($query_result) > 0) {

			$row	= mysql_fetch_object($query_result);

			$employee_id	= $row->sno;
		}

		return $employee_id;
	}

	/*
	* Method used to get the Burden Details of the company/customer
	*
	* @param	integer $pid - company/customer sno
	* @param	string $module - crm/accounting
	* @return	array $get_comp_burden_data - pay/bill burden type id and overwrite values
	*/
	function getLocBurdenDetails($pid,$module)
	{
		global $db;
		if($module == 'crm')
		{
			$btloc_table = 'staffoppr_location';
		}
		else if($module == 'acc')
		{
			$btloc_table = 'staffacc_location';
		}
		//Get the burden details. If null values, then ge the default zero burden details
		$get_comp_burden_sql = "SELECT
					IF(burden_type IS NULL OR burden_type = '',1,burden_type),
					IF(bt_overwrite IS NULL OR bt_overwrite = '','YES',bt_overwrite),
					IF(bill_burden_type IS NULL OR bill_burden_type = '',2,bill_burden_type),
					IF(bill_bt_overwrite IS NULL OR bill_bt_overwrite = '','YES',bill_bt_overwrite)
		FROM 	".$btloc_table."
		WHERE 	csno = '".$pid."' AND
				ltype='com'";
		$get_comp_burden_rs = mysql_query($get_comp_burden_sql,$db);
		$get_comp_burden_data = mysql_fetch_row($get_comp_burden_rs);
		return $get_comp_burden_data;
	}

	/*
	* Method used to get the Pay/Bill burden HTML form elements for selection in companies/customer/contacts pages
	* @param	string $module - calling the function from crm contact/company or accounting customer
	* @param	string $bt_details - pay/bill burden type details ( Pay Burden Type ID|Pay Burden overwrite|Bill Burden Type ID|Bill Burden Overwrite)
	* @return	string $returnStr - returning the Pay/Bill burden select box elements separated with ^^AKKENBTDETAILS^^ string
	*/
	function getBurdenDetailsFormElements($module='',$bt_details='')
	{
		global $db;

		//Getting the all active burden types
		$get_bt_list_sql = "SELECT  bt.sno, bt.burden_type_name, bt.ratetype FROM burden_types bt WHERE bt.bt_status = 'Active'";
		$get_bt_list_rs = mysql_query($get_bt_list_sql, $db);

		//Forming the array of all burden types in 2D arrray with BT sno as key element and burden type name and pay/bill burden flag
		$arr_burden_type = array();

		while ($row = mysql_fetch_object($get_bt_list_rs)) {
			$arr_burden_type[$row->sno]['burden_type']	= $row->burden_type_name;
			$arr_burden_type[$row->sno]['rate_type']	= $row->ratetype;
		}

		//Getting all the active burden items
		$get_bi_sql = "SELECT 	bi.sno,
							bim.bt_id,
							bi.burden_item_name,
							bi.burden_value,
							bi.burden_mode,
							bi.ratetype,
							bi.max_earned_amnt,
							bi.billable_status
							FROM  burden_items bi
							JOIN burden_items_map bim ON bim.bi_id = bi.sno
							WHERE bi.bi_status = 'Active' ";

		$get_bi_rs = mysql_query($get_bi_sql,$db);

		//Forming the burden items array with BT id as key element to display details when selected particular burden type
		$biArray = array();
		while ($row = mysql_fetch_array($get_bi_rs))
		{
			$btid = $row[1];
			$suf = "";
			if ($row[4] == 'percentage')
			{
				$suf = "%";
			}
			if (isset($biArray[$btid]))
			{
				if ($row[2] == 'Zero Pay Burden' || $row[2] == 'Zero Bill Burden') {
					$biArray[$btid] = $biArray[$btid] . " + " . number_format($row[3]) . $suf;
				} else {
					$biArray[$btid] = $biArray[$btid] . " + " . $row[2] . "-" . $row[3] . $suf;
				}
			}
			else
			{
				if ($row[2] == 'Zero Pay Burden' || $row[2] == 'Zero Bill Burden') {
					$biArray[$btid] = number_format($row[3]) . $suf;
				} else {
					$biArray[$btid] = $row[2] . "-" . $row[3] . $suf;
				}
			}
		}
		//Initialize the variables
		//Return string Pay Burden Select Box ^^AKKENBTDETAILS^^ Bill Burden select Box
		$returnStr = '';
		//Pay/Bill burden details and its overwrite values
		$pay_bt_id = '';
		$pay_bt_ovr = '';
		$bill_bt_id = '';
		$bill_bt_ovr = '';

		//Set the allow user to overwrite checkbox as checekd by default
		$pay_checked_fl = 'checked';
		$bill_checked_fl = 'checked';
		//In CRM contact the elements has to be disabled for the company information section by default
		$disabled_fl = '';

		//If this function is called from CRM contact, then make the elements disabled by default when loaded
		if($module == 'crmcont')
		{
			$disabled_fl = ' disabled="disabled" ';
		}
		// if the burden details are not blank, it means called from the edit page of contacts/companies/customer. override the values to select the parituclar burden details and checkbox
		if($bt_details != '')
		{
			$bt_details_exp = explode("|",$bt_details);
			$pay_bt_id = $bt_details_exp[0];
			$pay_bt_ovr = $bt_details_exp[1];
			$bill_bt_id = $bt_details_exp[2];
			$bill_bt_ovr = $bt_details_exp[3];

			if($pay_bt_ovr == 'NO')
			{
				$pay_checked_fl = '';
			}
			if($bill_bt_ovr == 'NO')
			{
				$bill_checked_fl = '';
			}

		}

		//Pay Burden Select Box
		$pay_burden_str = '<select name="burdenType" id="burdenType" onchange="getLocPayBurdenString(this)" '.$disabled_fl.'>';

		foreach ($arr_burden_type as $sno => $burden)
		{
			//if the burden is Pay Burden
			if ($burden['rate_type'] == 'payrate')
			{
				//Check if any burden item is associated to that burden type. if so, then form the option value
				if (isset($biArray[$sno]))
				{
					$optVal = $sno . "|" . $biArray[$sno];
				}
				else
				{
					$optVal = $sno . "|";
				}
				//Check for auto selected the saved burden types when called from edit pages of contacts/comapnies/customers
				$selected = "";
				if ($sno == $pay_bt_id && $pay_bt_id != '')
				{
					$selected = "selected";
				}
				//pay burden options
				$pay_burden_str .= '<option value="'.$optVal.'" '.$selected.' >'.$burden['burden_type'].'</option>';
			}
		}
		//pay burden check box of user overwrite option
		$pay_burden_str .= '</select><input type="checkbox" id="chkbtUserOverwrite" name="chkbtUserOverwrite" value="YES" '.$disabled_fl.' '.$pay_checked_fl.' /><span class="fontstyle">Allow user to overwrite?</span>';

		//Bill Burden Select Box
		$bill_burden_str .= '<select name="bill_burdenType" id="bill_burdenType" onchange="getLocBillBurdenString(this)" '.$disabled_fl.'>';

		foreach ($arr_burden_type as $sno => $burden)
		{
			//if the burden is Bill Burden
			if ($burden['rate_type'] == 'billrate')
			{
				//Check if any burden item is associated to that burden type. if so, then form the option value
				if (isset($biArray[$sno]))
				{
					$optVal = $sno . "|" . $biArray[$sno];
				}
				else
				{
					$optVal = $sno . "|";
				}
				//Check for auto selected the saved burden types when called from edit pages of contacts/comapnies/customers
				$selected = "";
				if ($sno == $bill_bt_id && $bill_bt_id != '')
				{
					$selected = "selected";
				}
				//bill burden options
				$bill_burden_str .= '<option value="'.$optVal.'" '.$selected.' >'.$burden['burden_type'].'</option>';
			}
		}
		//pay burden check box of user overwrite option
		$bill_burden_str .= '</select><input type="checkbox" id="chkbillbtUserOverwrite" name="chkbillbtUserOverwrite" value="YES"  '.$disabled_fl.' '.$bill_checked_fl.'  /><span class="fontstyle">Allow user to overwrite?</span>';

		//Form the return string
		$returnStr = $pay_burden_str."^^AKKENBTDETAILS^^".$bill_burden_str;
		return $returnStr;
	}

	/*
	* Method used to update the Pay/Bill Burden Details of companies/customers
	* @param	integer $pid - company/customer sno
	* @param	string $module - crm/accounting
	* @param	string $bt_details - pay/bill burden type details ( Pay Burden Type ID|Pay Burden overwrite|Bill Burden Type ID|Bill Burden Overwrite) to be updated
	* @return	no return
	*/
	function updLocBurdenDetails($pid,$module,$bt_details)
	{
		global $db;
		if($module == 'crm')
		{
			$btloc_table = 'staffoppr_location';
		}
		else if($module == 'acc')
		{
			$btloc_table = 'staffacc_location';
		}
		$comp_burden_details_exp = explode("|",$bt_details);
		$upd_burden_details_sql = "UPDATE ".$btloc_table." SET
											burden_type='".$comp_burden_details_exp[0]."',
											bill_burden_type='".$comp_burden_details_exp[1]."',
											bt_overwrite='".$comp_burden_details_exp[2]."',
											bill_bt_overwrite='".$comp_burden_details_exp[3]."'
										WHERE csno='".$pid."' AND ltype='com'
											";
		mysql_query($upd_burden_details_sql,$db);
	}
	function writeOffSelBox()
	{
		global $db;

		$query	= "SELECT r.sno, r.name, r.type, '', '', acc.tdesc
			    FROM reg_category r, ac_chart ac, ac_category acc
			    WHERE r.sno = ac.ChartID
			    AND r.status = 'ER'
			    AND acc.type = r.type
			    AND r.parent = (
			    SELECT r.sno
			    FROM reg_category r
			    WHERE r.name = 'Allowance for Doubtful Accounts' or r.name ='Adjustment Type')
			    ORDER BY r.name";
		$res = mysql_query($query, $db);

		$options = "<option value='sel'>-- Select Type --</option>";
		while($row = mysql_fetch_array($res))
		{
		  
			 if($row['name']=="Available Credits" && PAYROLL_PROCESS_BY=="QB"){
			 //$options = '';
			 } else{
			 $dispName = $row['name'];
             $options .= "<option value=\"".$row['sno']."\" title=\"".html_tls_entities($row['name'], ENT_QUOTES)."\">".html_tls_entities($dispName, ENT_QUOTES)."</option>";
			}

		}
		return $options;
	}
//Function to get AOB Form Groups list in Website Management
function getFormGroups($formgroups, $webaction) {
    global $db;

    $numargs = func_num_args();
    if ($numargs == 2) {
        $formgroups = func_get_arg(0);
        $webaction = func_get_arg(1);
        $sloc = explode(",", $formgroups);
        $Form_groups = "<select style='width: 250px;' id='form_groups'>";
        $fque = "SELECT sno,group_name,status FROM apt_forms_groups WHERE status='Y' ".((AOB_ENABLED!="Y")? ' AND sno IN (1,2) ' : '')." order by sno ASC ";
        $fres = mysql_query($fque, $db);


        $newquery = "SELECT jf.apt_group_id,afg.group_name,afg.status
                       FROM jobposting_pref jf
                       LEFT JOIN apt_forms_groups afg ON jf.apt_group_id=afg.sno WHERE afg.status='Y' ";


        $fsearchres = mysql_query($newquery, $db);

        $faptgid = mysql_fetch_row($fsearchres);

        if ($webaction == 'addweb') {
            while ($frow = mysql_fetch_row($fres)) {

                if (in_array($frow[0], $faptgid)) {


                    $Form_groups.="<option selected value='" . $frow[0] . "'>" . $frow[1] . "</option>";
                } else {
                    $Form_groups.="<option value='" . $frow[0] . "'>" . $frow[1] . "</option>";
                }
            }
            $Form_groups.="</select>";

            return $Form_groups;
        } else {
            while ($frow = mysql_fetch_row($fres)) {

                if (in_array(0, $sloc) || in_array('0', $sloc) || in_array(null, $sloc)|| in_array("", $sloc)) {
                    if (in_array($frow[0], $faptgid)) {


                        $Form_groups.="<option selected value='" . $frow[0] . "'>" . $frow[1] . "</option>";
                    } else {
                        $Form_groups.="<option value='" . $frow[0] . "'>" . $frow[1] . "</option>";
                    }
                } else if (in_array($frow[0], $sloc)) {
                    $Form_groups.="<option selected value='" . $frow[0] . "'>" . $frow[1] . "</option>";
                } else {
                    $Form_groups.="<option value='" . $frow[0] . "'>" . $frow[1] . "</option>";
                }
            }
            $Form_groups.="</select>";

            return $Form_groups;
        }
    }
}

//Array to JSON conversion
function array2json($arr) {

	$parts = array();
	$is_list = false;

	//Find out if the given array is a numerical array
	$keys = array_keys($arr);
	$max_length = count($arr)-1;
	if(($keys[0] == 0) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1
		$is_list = true;
		for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position
			if($i != $keys[$i]) { //A key fails at position check.
				$is_list = false; //It is an associative array.
				break;
			}
		}
	}

	foreach($arr as $key=>$value) {
		if(is_array($value)) { //Custom handling for arrays
			if($is_list) $parts[] = array2json($value); /* :RECURSION: */
			else $parts[] = '"' . $key . '":' . array2json($value); /* :RECURSION: */
		} else {
			$str = '';
			if(!$is_list) $str = '"' . $key . '":';

			//Custom handling for multiple data types
			if(is_numeric($value)) $str .= '"' . $value. '"';//Numbers
			elseif($value === false) $str .= 'false'; //The booleans
			elseif($value === true) $str .= 'true';
			else $str .= '"' . addslashes($value) . '"'; //All other things
			// :TODO: Is there any more datatype we should be in the lookout for? (Object?)

			$parts[] = $str;
		}
	}
	$json = implode(',',$parts);

	if($is_list) return '[' . $json . ']';//Return numerical JSON
	return '{' . $json . '}';//Return associative JSON
}

    //Ticket# 808057 - This function used to display users based on preferences, while sharing the report.
	function shareReports($username, $analyticsrptmod,$dbcon='')
	{
		global $rptdb ,$db;

		$whrcond = '';

		if ($analyticsrptmod == 'crm')
		{
			$whrcond	= " AND sysuser.crm != 'NO' ";
		}
		else if($analyticsrptmod == 'hrm')
		{
			$whrcond	= " AND sysuser.hrm != 'NO' ";
		}
		else if($analyticsrptmod == 'accounting')
		{
			$whrcond	= " AND sysuser.accounting != 'NO' ";
		}
		else if($analyticsrptmod == 'collaboration')
		{
			$whrcond	= " AND sysuser.collaboration != 'NO' ";
		}

		$getShareUsers	= "SELECT
					users.username, users.name, users.userid
				FROM
					users
				LEFT JOIN
					sysuser ON users.username = sysuser.username
				WHERE
					users.status != 'DA'
					AND users.username != '".$username."'
					AND sysuser.analytics != 'NO'
					".$whrcond."
				ORDER BY
					users.name";
		if($dbcon == '') {
                    $resShareUsers	= mysql_query($getShareUsers, $rptdb);
                }else {
                    $resShareUsers	= mysql_query($getShareUsers, $db);
                }
		$shareOptions	= "";

		if(mysql_num_rows($resShareUsers) > 0)
		{
			while($rowShareUsers = mysql_fetch_row($resShareUsers))
			{
				$shareOptions	.= "<option value='".$rowShareUsers[0]."'>".$rowShareUsers[1]."</option>";
			}
		}
		else
		{
			$shareOptions	.= "<option value='none'>-- None --</option>";
		}

		return $shareOptions;
	}


// UOM: To retrieve the ratestypes for display

function getNewRateTypes($rate = null){
	global $db;

	if($rate == null )
	{
		$rateQuery = "SELECT * FROM manage_uom WHERE status='Active' order by sno ASC ";
		$rateResponse = mysql_query($rateQuery, $db);
	while ($rateRow = mysql_fetch_row($rateResponse))
	{
		if($rateRow[4]=='Y'){

		  $rate_option.='<option  value="'. $rateRow[2] .'" selected >'. $rateRow[1] . '</option>';
		}else{
		  $rate_option.='<option  value="'. $rateRow[2] .'">'. $rateRow[1] . '</option>';
		}

	}
	}else{

		$rateQuery = "SELECT * FROM manage_uom WHERE status='Active' order by sno ASC ";
		$rateResponse = mysql_query($rateQuery, $db);
		while ($rateRow = mysql_fetch_row($rateResponse))
		{
			if($rateRow[2]==$rate){
		$rate_option.='<option  value="'. $rateRow[2] .'" selected >'. $rateRow[1] . '</option>';
			}else{
		$rate_option.='<option  value="'. $rateRow[2] .'" >'. $rateRow[1] . '</option>';
				}
		}
		}
	return $rate_option;

}


//Purpose: To retrieve the rate type for displaying in invoice page.
function getUOMtype_inv($rate = '',$typePDF = ''){
	$rate_dis = '';
	switch($rate){
		case 'UOM_DAY':
			$rate_dis = 'DAY';
			break;
		case 'UOM_MILE':
			$rate_dis = 'MILE';
			break;
		case 'UOM_UNIT':
			$rate_dis = 'UNIT';
			break;
		default:
			$rate_dis = $rate;
	}
	if($typePDF == '' && ($rate == 'UOM_DAY' || $rate == 'UOM_MILE' || $rate == 'UOM_UNIT')){
		$rate_dis = $rate_dis.'*';
	}
        return $rate_dis;

}
//[#809208] - This function is used to display users Start date/End Date Years, 20 years for past years and 10 years to future.
function displayPastFutureYears($selYear = '')
{
	$displayYear = "";
	for($i=date('Y')-20;$i<=date('Y')+10;$i++)
		$displayYear.= "<OPTION ".compose_sel($i,$selYear)." VALUE=".$i.">".$i."</option>";

	return $displayYear;
}

//[#809710] How to Restrict Edit Lists in Akken
function editListsAccess()
{
	$adminprefRes= $_SESSION['adminpref'];
	$findme   = '+14+';
	$flag = false;
	$pos = strpos($adminprefRes, $findme);
		if ($pos!== false)
			{
				$flag = true;
			}
	return $flag;
}
// Akkupay Phase -1 changes to retrieve paygroupcode
function getPayGroupCodes($paygroupcodesno = null){
	global $db;

	if($paygroupcodesno == null || $paygroupcodesno == '')
	{
		$paygroupQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='PG' order by sno ASC ";
		$Response = mysql_query($paygroupQuery, $db);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $paygrpcode_option.='<option  value="'. $Row[0] .'">'. $Row[2] . '</option>';  
                }
	}else{
		  
		$paygroupQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='PG' order by sno ASC ";
		$Response = mysql_query($paygroupQuery, $db);
			while ($Row = mysql_fetch_row($Response)) 
			{
        		if($Row[0]==$paygroupcodesno)
				{ 
					$paygrpcode_option.='<option  value="'. $Row[0] .'" selected >'. $Row[2] . '</option>';
				}else
				{
					$paygrpcode_option.='<option  value="'. $Row[0] .'" >'. $Row[2] . '</option>';
				}
			}
        }
        
    return $paygrpcode_option;
}
// Akkupay Phase -1 changes to retrieve pay code
function getPayCodes($paycodesno = null){
    
    global $db;

	if($paycodesno == null || $paycodesno == '' )
	{
		$paycodeQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='PY' order by sno ASC ";
		$Response = mysql_query($paycodeQuery, $db);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $paycode_option.='<option  value="'. $Row[0] .'">'. $Row[2] . '</option>';  
                }
	}else{
		  
		$paycodeQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='PY' order by sno ASC ";
		$Response = mysql_query($paycodeQuery, $db);
				while ($Row = mysql_fetch_row($Response)) 
				{
                    if($Row[0]==$paycodesno){ 
						$paycode_option.='<option  value="'. $Row[0] .'" selected >'. $Row[2] . '</option>';
					}else{
						$paycode_option.='<option  value="'. $Row[0] .'" >'. $Row[2] . '</option>';
						}
				}
      }
        
   return $paycode_option;
       
}
// Akkupay Phase -1 changes to retrieve deduction codes
function getDeductionCodes($deducodesno = null,$dedtype=''){
    
    global $db;
        
        $ded_type ="";
        if($dedtype=='garnishments'){
            $ded_type ="AND ded_type='W'";   
        }else if($dedtype=='deductions'){
            $ded_type ="AND ded_type!='W'";   
        }else{
            $ded_type ="";  
        }
		
	if($deducodesno == null || $deducodesno == '' )
	{
		$dedcodeQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='DE' ".$ded_type." order by sno ASC ";
		$Response = mysql_query($dedcodeQuery, $db);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $dedcode_option.='<option  value="'. $Row[0] .'">'. $Row[2] . '</option>';  
                }
	}else{
		  
		$dedcodeQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='DE' ".$ded_type." order by sno ASC ";
		$Response = mysql_query($dedcodeQuery, $db);
				while ($Row = mysql_fetch_row($Response)) 
				{
                    if($Row[0]==$deducodesno){ 
						$dedcode_option.='<option  value="'. $Row[0] .'" selected >'. $Row[2] . '</option>';
					}else{
						$dedcode_option.='<option  value="'. $Row[0] .'" >'. $Row[2] . '</option>';
						}
				}
        }
        
    return $dedcode_option;
    
    
}
// Akkupay Phase -1 changes to retrieve garnishment periods
function getGarnishmentCodes($garnishmentcode = null){
    
    global $db;

	if($garnishmentcode == null || $garnishmentcode == '' )
	{
		$garnishQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='GT' order by sno ASC ";
		$Response = mysql_query($garnishQuery, $db);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $garnish_option.='<option  value="'. $Row[2] .'">'. $Row[3] . '</option>';  
                }
	}else{
		  
		$garnishQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='GT' order by sno ASC ";
		$Response = mysql_query($garnishQuery, $db);
				while ($Row = mysql_fetch_row($Response)) 
				{
                    if($Row[2]==$garnishmentcode){ 
						$garnish_option.='<option  value="'. $Row[2] .'" selected >'. $Row[3] . '</option>';
					}else{
						$garnish_option.='<option  value="'. $Row[2] .'" >'. $Row[3] . '</option>';
					}
				}
        }
        
  return $garnish_option;
       
}
// Akkupay Phase -1 changes to retrieve filling status codes 
function getFillingStatusCodes($statecode,$fillingcode){
    
    global $maindb;
                
    	$fillingQuery = "SELECT sno,state,code,description,status FROM mphr_state_allowances WHERE status='Y' AND state='".$statecode."' order by description ASC ";
		$Response = mysql_query($fillingQuery,$maindb);
		while ($Row = mysql_fetch_row($Response)) 
		{
        	if($Row[2]==$fillingcode && $Row[1]==$statecode ){ 
				$filling_option.='<option  value="'. $Row[2] .'" selected >'. $Row[3] . '</option>';
			}else{
				$filling_option.='<option  value="'. $Row[2] .'" >'. $Row[3] . '</option>';
			}
		}
                
        return $filling_option;
       
}
// Akkupay Phase -1 changes to retrieve state codes 
function getSDUStateCodes($statecode=null){
    
    global $db;
        $sqrystate_codes = "select state_id,state_abbr from state_codes where state_abbr!='' and  state_abbr!='null' order by state_abbr";
        $srsstate_codes = mysql_query($sqrystate_codes, $db);
    	while ($srowstate_codes = mysql_fetch_row($srsstate_codes)) {
        	if($statecode==null){
         		 $statecode_option.='<option  value="'. $srowstate_codes[1] .'"  >'. $srowstate_codes[1] . '</option>';  
       		 }else if($statecode == $srowstate_codes[1]){
           		$statecode_option.='<option  value="'. $srowstate_codes[1] .'" selected >'. $srowstate_codes[1] . '</option>'; 
        	 }else {
           	 	$statecode_option.='<option  value="'. $srowstate_codes[1] .'"  >'. $srowstate_codes[1] . '</option>';  
        	 }
    	}
    	return $statecode_option;
}
// Akkupay Phase -1 changes to retrieve primary state codes 
function getPrimaryStatecodes($statecode){
    
        global $maindb;
        $primary_allowances_sql = "SELECT * FROM  mphr_primary_allowances 
                                WHERE state = '".$statecode."' AND status='Y'";
        $primary_allowances = mysql_query($primary_allowances_sql,$maindb);	
        $primary_allowances_row = mysql_num_rows($primary_allowances);
            if($primary_allowances_row>0){
                $restrictFlag="YES";
            }else{
                $restrictFlag="NO";   
            } 
            
       return $restrictFlag;     
}

//funtion to bulid the join conditions for css user assignment preferences
function buildAsgnConditions($asgnPreferencesCSS)
{
	global $db,$username;

	$query 	= "SELECT con_id FROM staffacc_contactacc WHERE username='$username'";
	$rs 	= mysql_query($query,$db);
	$row	= mysql_fetch_row($rs);

	$conJoin 	= "";
	$chkContact 	= "";
	$chkReportTo 	= "";
	$chkAsgnBillContact = "";

	if(strpos($asgnPreferencesCSS,"+1+")) // Customer CheckBox Selected
	{
		if(strpos($asgnPreferencesCSS,"+4")) // and Billing contact
		{
			$conJoin 	= " OR hrcon_jobs.bill_contact={$row[0]} ";
			$asgnBillContactCon	= "YES";
		}
		$conJoin	= "(staffacc_contactacc.username=$username $conJoin)";
	}
	else
	{
		if(strpos($asgnPreferencesCSS,"+2+"))
		{
			$chkContact 	= "hrcon_jobs.contact = staffacc_contactacc.con_id";
		}
		if(strpos($asgnPreferencesCSS,"+3+"))
		{
			$chkReportTo 	= "hrcon_jobs.manager = staffacc_contactacc.con_id";
		}
		if(strpos($asgnPreferencesCSS,"+4"))
		{
			$chkAsgnBillContact	= "hrcon_jobs.bill_contact={$row[0]} ";
			$asgnBillContactCon	= "YES";
		}

		//preparing the join conditions
		if($chkContact!="" && $chkReportTo=="" && $chkAsgnBillContact=="") // Only Contact
		{
			$conJoin	= "AND ($chkContact)";
		}
		else
		if($chkContact=="" && $chkReportTo!="" && $chkAsgnBillContact=="") // Only Report To
		{
			$conJoin	= "AND ($chkReportTo)";
		}
		else
		if($chkContact=="" && $chkReportTo=="" && $chkAsgnBillContact!="") // Only Billing Contact
		{
			$conJoin	= "AND ($chkAsgnBillContact)";
		}
		else
		if($chkContact!="" && $chkReportTo!="" && $chkAsgnBillContact=="") // Contact and Report To
		{
			$conJoin	= "AND ($chkContact OR $chkReportTo)";
		}
		else
		if($chkContact!="" && $chkReportTo=="" && $chkAsgnBillContact!="") // Contact and Billing Contact
		{
			$conJoin	= "AND ($chkContact OR $chkAsgnBillContact)";
		}
		else
		if($chkContact=="" && $chkReportTo!="" && $chkAsgnBillContact!="") // Report to and Billing Contact
		{
			$conJoin	= "AND ($chkReportTo OR $chkAsgnBillContact)";
		}
		else
		if($chkContact!="" && $chkReportTo!="" && $chkAsgnBillContact!="") // Contact, Report to AND Billing Contact
		{
			$conJoin	= "AND ($chkContact OR $chkReportTo OR $chkAsgnBillContact)";
		}

		$conJoin	= "staffacc_contactacc.username=$username $conJoin";
	}

	return $asgnBillContactCon."|".$conJoin;
}

function buildTimesheetConditions($tsPreferencesCSS,$showEmplyoees='',$CtVal='')
{
	global $db,$username;

	$query 	= "SELECT con_id FROM staffacc_contactacc WHERE username='$username'";
	$rs 	= mysql_query($query,$db);
	$row	= mysql_fetch_row($rs);

	$conJoin 	= "";
	$chkContact 	= "";
	$chkReportTo 	= "";
	$chkAsgnBillContact = "";
	$asgnBillContactCon = "";
	$condCk		= "";
	$conJoinBill	= "";

	if(strpos($tsPreferencesCSS,"+3+")) // Customer CheckBox Selected
	{
		if(strpos($tsPreferencesCSS,"+9+")) // and Billing Contact
		{
			$conJoinBill 		= " AND hrcon_jobs.bill_contact={$row[0]} ";
			$asgnBillContactCon	= "YES";
			$showEmplyoeesCond	= " AND (hrcon_jobs.bill_contact=$CtVal) ";
		}
	}
	else
	{
		if(strpos($tsPreferencesCSS,"+4+"))
		{
			$chkContact 	= "hrcon_jobs.contact = staffacc_contactacc.con_id";
			$condCk		= " staffacc_contactacc.username = $username  AND";
		}
		if(strpos($tsPreferencesCSS,"+5+"))
		{
			$chkReportTo 	= "hrcon_jobs.manager = staffacc_contactacc.con_id";
			$condCk		= " staffacc_contactacc.username = $username  AND";
		}
		if(strpos($tsPreferencesCSS,"+9+"))
		{
			$chkAsgnBillContact	= "hrcon_jobs.bill_contact={$row[0]} ";
			$asgnBillContactCon	= "YES";
			$conJoinBill 		= " AND hrcon_jobs.bill_contact={$row[0]} ";
		}

		//preparing the join conditions

		if($chkContact!="" && $chkReportTo=="" && $chkAsgnBillContact=="") // Only Contact
		{
			$conJoin	= " LEFT JOIN staffacc_contactacc ON ($chkContact)";
		}
		else
		if($chkContact=="" && $chkReportTo!="" && $chkAsgnBillContact=="") // Only Reports To
		{
			$conJoin	= " LEFT JOIN staffacc_contactacc ON ($chkReportTo)";
		}
		else
		if($chkContact=="" && $chkReportTo=="" && $chkAsgnBillContact!="") // Only Billing Contact
		{
			$conJoin	= " AND ($chkAsgnBillContact)";
		}
		else
		if($chkContact!="" && $chkReportTo!="" && $chkAsgnBillContact=="") // Contact and Reports to
		{
			$conJoin	= "LEFT JOIN staffacc_contactacc ON ($chkContact OR $chkReportTo)";
		}
		else
		if($chkContact!="" && $chkReportTo=="" && $chkAsgnBillContact!="") // Contact and Billing Contact
		{
			$conJoin	= "LEFT JOIN staffacc_contactacc ON ($chkContact OR $chkAsgnBillContact)";
		}
		else
		if($chkContact=="" && $chkReportTo!="" && $chkAsgnBillContact!="") // Reports to and Billing Contact
		{
			$conJoin	= "LEFT JOIN staffacc_contactacc ON ($chkReportTo OR $chkAsgnBillContact)";
		}
		else
		if($chkContact!="" && $chkReportTo!="" && $chkAsgnBillContact!="") // Contact, reports to and billing contact
		{
			$conJoin	= "LEFT JOIN staffacc_contactacc ON ($chkContact OR $chkReportTo OR $chkAsgnBillContact)";
		}
	}
	$showEmplyoeesCond = "|".$showEmplyoeesCond;
	if($showEmplyoees != "YES")
	{
	    $showEmplyoeesCond = "";
	}

	return $asgnBillContactCon."|".$conJoin."|".$condCk."|".$conJoinBill.$showEmplyoeesCond;
}

function buildExpnesesConditions($expnPreferencesCSS,$showEmplyoees='',$CtVal='')
{
	global $db,$username;

	$query 	= "SELECT con_id FROM staffacc_contactacc WHERE username='$username'";
	$rs 	= mysql_query($query,$db);
	$row	= mysql_fetch_row($rs);

	$conJoin 	= "";
	$chkContact 	= "";
	$chkReportTo 	= "";
	$chkAsgnBillContact = "";
	$asgnBillContactCon = "";
	$condCk		= "";
	$conJoinBill	= "";
	$showEmplyoeesCond = "";

	if(strpos($expnPreferencesCSS,"+3+")) // Customer CheckBox Selected
	{
		if(strpos($expnPreferencesCSS,"+8+")) // and Billing Contact
		{
			$asgnBillContactCon	= "YES";
			$conJoinBill 		= " AND hrcon_jobs.bill_contact={$row[0]} ";
			$showEmplyoeesCond	= " AND (hrcon_jobs.bill_contact=$CtVal) ";
		}
	}
	else
	{
		if(strpos($expnPreferencesCSS,"+4+")) // Contact
		{
			$chkContact 		= "hrcon_jobs.contact = staffacc_contactacc.con_id";
			$condCk			= " staffacc_contactacc.username = $username  AND";
			$chkContactVal  	= "hrcon_jobs.contact = $CtVal";
		}
		if(strpos($expnPreferencesCSS,"+5+")) // Reports To
		{
			$chkReportTo 		= "hrcon_jobs.manager = staffacc_contactacc.con_id";
			$condCk			= " staffacc_contactacc.username = $username  AND";
			$chkReportToVal		= "hrcon_jobs.manager = $CtVal";
		}
		if(strpos($expnPreferencesCSS,"+8+")) // Asgn Billing contact
		{
			$asgnBillContactCon	= "YES";
			$chkAsgnBillContact	= "hrcon_jobs.bill_contact={$row[0]} ";			
			$conJoinBill 		= " AND hrcon_jobs.bill_contact={$row[0]} ";
			$chkAsgnBillContactVal	= "hrcon_jobs.bill_contact= $CtVal";
		}

		//preparing the join conditions

		if($chkContact!="" && $chkReportTo=="" && $chkAsgnBillContact=="") // Only Contact
		{
			$conJoin	= " LEFT JOIN staffacc_contactacc ON ($chkContact)";
			$showEmplyoeesCond = "AND ($chkContactVal)";
		}
		else
		if($chkContact=="" && $chkReportTo!="" && $chkAsgnBillContact=="") // Only Reports To
		{
			$conJoin	= " LEFT JOIN staffacc_contactacc ON ($chkReportTo)";
			$showEmplyoeesCond = "AND ($chkReportToVal)";
		}
		else
		if($chkContact=="" && $chkReportTo=="" && $chkAsgnBillContact!="") // Only Billing Contact
		{
			$conJoin	= " AND ($chkAsgnBillContact)";
			$showEmplyoeesCond = "AND ($chkAsgnBillContactVal)";
		}
		else
		if($chkContact!="" && $chkReportTo!="" && $chkAsgnBillContact=="") // Contact and Reports to
		{
			$conJoin	= "LEFT JOIN staffacc_contactacc ON ($chkContact OR $chkReportTo)";
			$showEmplyoeesCond = "AND ($chkContactVal OR $chkReportToVal)";
		}
		else
		if($chkContact!="" && $chkReportTo=="" && $chkAsgnBillContact!="") // Contact and Billing Contact
		{
			$conJoin	= "LEFT JOIN staffacc_contactacc ON ($chkContact OR $chkAsgnBillContact)";
			$showEmplyoeesCond = "AND ($chkContactVal OR $chkAsgnBillContactVal)";
		}
		else
		if($chkContact=="" && $chkReportTo!="" && $chkAsgnBillContact!="") // Reports to and Billing Contact
		{
			$conJoin	= "LEFT JOIN staffacc_contactacc ON ($chkReportTo OR $chkAsgnBillContact)";
			$showEmplyoeesCond = "AND ($chkReportToVal OR $chkAsgnBillContactVal)";
		}
		else
		if($chkContact!="" && $chkReportTo!="" && $chkAsgnBillContact!="") // Contact, reports to and billing contact
		{
			$conJoin	= "LEFT JOIN staffacc_contactacc ON ($chkContact OR $chkReportTo OR $chkAsgnBillContact)";
			$showEmplyoeesCond = "AND ($chkContactVal OR $chkReportToVal OR $chkAsgnBillContactVal)";
		}
	}
	$showEmplyoeesCond = "|".$showEmplyoeesCond;
	if($showEmplyoees != "YES")
	{
	    $showEmplyoeesCond = "";
	}
	return $asgnBillContactCon."|".$conJoin."|".$condCk."|".$conJoinBill.$showEmplyoeesCond;
}
// Function for phone number field query field replace functionality
function phoneNoFilterAsFld($value,$asFieldsKey){
	// Replacing phone number fields query field to map any numbers or with -,(,)'s and spaces.
	$asFields[$asFieldsKey] = "REPLACE(REPLACE(REPLACE(REPLACE($value,'(',''),')',''),'-',''),' ','')"; 
	return $asFields[$asFieldsKey];
}
// Function for phone number field search field replace functionality
function phoneNoFilterGridFld($value,$gridSearchFields,$asFieldsKey){
	// Replacing phone number fields search field to map any numbers  or with -,(,)'s and spaces.
	$search = array('-', '(', ')', ' ');
	$replace = array('', '', '','');
	$gridSearchFields[$asFieldsKey-1] = str_replace($search, $replace, $gridSearchFields[($asFieldsKey-1)]);
	return $gridSearchFields[$asFieldsKey-1];
}
function desiredJobType($gridSearchFields,$asFieldsKey){
	// Replacing JobType with pipes
	$value_arr = explode(',',$gridSearchFields[$asFieldsKey-1]);
	$typeArr = array();
	$typeArr[0] = '';$typeArr[1]='';$typeArr[2]='';
	foreach($value_arr as $val){
		if($val == 'Permanent' || $val == 'permanent'){
			$typeArr[0]=$val;
		}
		else if($val == 'Intern' || $val == 'intern'){
			$typeArr[1]=$val;
		}
		else if($val == 'Contract' || $val == 'contract'){
			$typeArr[2]=$val;
		}
	}
	
		
	$desJtype = implode('|',$typeArr); 
	if($desJtype != '||'){
		$gridSearchFields[$asFieldsKey-1] = $desJtype;
	}
	return $gridSearchFields[$asFieldsKey-1];
}
// Akkupay Phase -1 changes to retrieve overridetype 
function getOverrideTypeCodes($overidecode = null){
    
    global $maindb;

	if($overidecode == null || $overidecode == '' )
	{
		$overRideQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='fed_over_type' order by sno ASC ";
		$Response = mysql_query($overRideQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $override_option.='<option  value="'. $Row[1] .'">'. $Row[0] . '</option>';  
                }
	}else{
		  
		$overRideQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='fed_over_type' order by sno ASC ";
		$Response = mysql_query($overRideQuery, $maindb);
				while ($Row = mysql_fetch_row($Response)) 
				{
                    if($Row[1]==$overidecode){ 
						$override_option.='<option  value="'. $Row[1] .'" selected >'. $Row[0] . '</option>';
					}else{
						$override_option.='<option  value="'. $Row[1] .'" >'. $Row[0] . '</option>';
					}
				}
        }
        
    return $override_option;
       
}
// Akkupay Phase -1 changes to retrieve workiste codes 
function getworkSiteCodes($worksitecode = null,$candID){
    
        global $db;
        
        $locationcodeQuery = "select location from hrcon_compen where emp_id ='".$candID."' and ustatus='active'";
        $locationResponse = mysql_query($locationcodeQuery, $db);
        $locatRow = mysql_fetch_row($locationResponse);
      
    
     if($worksitecode == null || $worksitecode == '' )
	{
                
		$workSiteQuery = " SELECT sno,locid,type,code,description,status
                                    FROM mphr_codes 
                                    WHERE status='Y' AND type='WS' AND locid='".$locatRow[0]."'
                                    ORDER BY code ASC "; 
                
		$Response = mysql_query($workSiteQuery, $db);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $workSite_option.='<option  value="'. $Row[3] .'">'. $Row[3] . '</option>';  
                }
	}else{
		  
		$workSiteQuery = "SELECT sno,locid,type,code,description,status
                                    FROM mphr_codes 
                                    WHERE status='Y' AND type='WS' AND locid='".$locatRow[0]."'
                                    ORDER BY code ASC ";
		$Response = mysql_query($workSiteQuery, $db);
				while ($Row = mysql_fetch_row($Response)) 
				{
                    if($Row[3]==$worksitecode){ 
						$workSite_option.='<option  value="'. $Row[3] .'" selected >'. $Row[3] . '</option>';
					}else{
						$workSite_option.='<option  value="'. $Row[3] .'" >'. $Row[3] . '</option>';
					}
				}
        }
        
    return $workSite_option;    
}
// Akkupay Phase -1 changes regarding retrieval of calculation methods 
function getCalculationMethodTypes($calc_method = null){
    
    global $maindb;

	if($calc_method == null || $calc_method == '' )
	{
		$calc_methodQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='calc_method' order by sno ASC ";
		$Response = mysql_query($calc_methodQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $calc_method_option.='<option  value="'. $Row[1] .'">'. $Row[0] . '</option>';  
                }
	}else{
		  
		$calc_methodQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='calc_method' order by sno ASC ";
		$Response = mysql_query($calc_methodQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        if($Row[1]==$calc_method){ 
                                $calc_method_option.='<option  value="'. $Row[1] .'" selected >'. $Row[0] . '</option>';
                        }else{
                                $calc_method_option.='<option  value="'. $Row[1] .'" >'. $Row[0] . '</option>';
                        }
                }
        }
        
    return $calc_method_option;
       
}

function getShiftName($shiftId)
{
    global $db;
    $query = "SELECT sm.sno, sm.shiftname, sm.shiftcode, sm.shiftcolor,sm.shiftstatus FROM shift_setup sm WHERE sm.sno ='".$shiftId."'";
    $rs 	= mysql_query($query,$db);
    $row	= mysql_fetch_row($rs);
    return $row['1'];
}


// Akkupay Phase -2 changes to retrieve deduction periods
function getAkkDeductPeriods($deductionPeriods){
	global $maindb;

	$deductPeriods=explode(",",$deductionPeriods);

	$deduct_Periods="<select style='width: 250px;' id=akk_deductperiods multiple=multiple>";
	$ique="SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='akku_deduct_period' order by sno ASC";
	$ires=mysql_query($ique,$maindb);
	while($irow=mysql_fetch_row($ires))
	{
		if(in_array($irow[0],$deductPeriods))
			$deduct_Periods.="<option selected value='".$irow[1]."'>".$irow[0]."</option>";
		else
			$deduct_Periods.="<option value='".$irow[1]."'>".$irow[0]."</option>";
	}
	$deduct_Periods.="</select>";

	return $deduct_Periods;
}
// Akkupay Phase -2 changes to retrieve deduction calculation methods
function getDeductionCalcMethods($calc_method = null){
    
    global $maindb;

	if($calc_method == null || $calc_method == '' )
	{
		$calc_methodQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='deduct_calc_method' order by sno ASC ";
		$Response = mysql_query($calc_methodQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $calc_method_option.='<option  value="'. $Row[1] .'">'. $Row[0] . '</option>';  
                }
	}else{
		  
		$calc_methodQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='deduct_calc_method' order by sno ASC ";
		$Response = mysql_query($calc_methodQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        if($Row[1]==$calc_method){ 
                                $calc_method_option.='<option  value="'. $Row[1] .'" selected >'. $Row[0] . '</option>';
                        }else{
                                $calc_method_option.='<option  value="'. $Row[1] .'" >'. $Row[0] . '</option>';
                        }
                }
        }
        
    return $calc_method_option;
       
}
// Akkupay Phase -2 changes to retrieve Insurance Class 
function getInsuranceClassTypes($insurance_class = null){
    
    global $maindb;

	if($insurance_class == null || $insurance_class == '' )
	{
		$insurance_classQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='insurance_class' order by sno ASC ";
		$Response = mysql_query($insurance_classQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $insurance_class_option.='<option  value="'. $Row[1] .'">'. $Row[0] . '</option>';  
                }
	}else{
		  
		$insurance_classQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='insurance_class' order by sno ASC ";
		$Response = mysql_query($insurance_classQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        if($Row[1]==$insurance_class){ 
                                $insurance_class_option.='<option  value="'. $Row[1] .'" selected >'. $Row[0] . '</option>';
                        }else{
                                $insurance_class_option.='<option  value="'. $Row[1] .'" >'. $Row[0] . '</option>';
                        }
                }
        }
        
    return $insurance_class_option;
       
}
// Akkupay Phase -2 changes to retrieve maximum Basis
function getMaximumBasis($max_basis = null){
    
    global $maindb;

	if($max_basis == null || $max_basis == '' )
	{
		$max_basisQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='maximum_basis' order by sno ASC ";
		$Response = mysql_query($max_basisQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $max_basis_option.='<option  value="'. $Row[1] .'">'. $Row[0] . '</option>';  
                }
	}else{
		  
		$max_basisQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='maximum_basis' order by sno ASC ";
		$Response = mysql_query($max_basisQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        if($Row[1]==$max_basis){ 
                                $max_basis_option.='<option  value="'. $Row[1] .'" selected >'. $Row[0] . '</option>';
                        }else{
                                $max_basis_option.='<option  value="'. $Row[1] .'" >'. $Row[0] . '</option>';
                        }
                }
        }
        
    return $max_basis_option;
       
}
// Akkupay Phase-2 changes to retrieval of state alternate filing codes
Function getAltFillingStatusCodes($statecode,$fillingcode){
    
 global $maindb;
                
    	$fillingQuery = "SELECT sno,state,code,description,status FROM mphr_state_alt_filing WHERE status='Y' AND state='".$statecode."' order by sno ASC ";
		$Response = mysql_query($fillingQuery,$maindb);
		while ($Row = mysql_fetch_row($Response)) 
		{
        	if($Row[2]==$fillingcode && $Row[1]==$statecode ){ 
				$filling_option.='<option serialno="'.$Row[0].'" value="'. $Row[2] .'" selected >'. $Row[3] . '</option>';
			}else{
				$filling_option.='<option serialno="'.$Row[0].'" value="'. $Row[2] .'" >'. $Row[3] . '</option>';
			}
		}
                
        return $filling_option;   
    
}

// Akkupay worksite codes on placement screen
function getworkSiteCodesforPlacement($loc_id)
{
    
        global $db;
                
		$workSiteQuery = " SELECT sno,locid,type,code,description,status
                                    FROM mphr_codes 
                                    WHERE status='Y' AND type='WS' AND locid='".$loc_id."'
                                    ORDER BY code ASC "; 
                
		$response = mysql_query($workSiteQuery, $db);
        while ($row = mysql_fetch_row($response)) 
        {
                $workSite_option.='<option  value="'. $row[3] .'">'. $row[3] . '</option>';  
        }

        
    return $workSite_option;    
}

/** Get the decimal preferences from company settings  **/
function getDecimalPreference()
{
    global $db;
    $companyInfoQry="select decimal_pref from company_info";
    $companyInfoRes=mysql_query($companyInfoQry,$db);
    $companyInfoRow=mysql_fetch_row($companyInfoRes);         
    $precision = (!empty($companyInfoRow[0])) ? $companyInfoRow[0] : 2; 
    return $precision;
                 
}
//Function to check whether login user is ESS or not
function chkLoginUserIsEss($username)
{
	global $db;
	$chk_query = "SELECT emp_list.sno SNO, emp_list.username, emp_list.name
FROM   emp_list
LEFT JOIN users ON emp_list.username = users.username
WHERE  emp_list.lstatus != 'DA' AND emp_list.lstatus != 'INACTIVE' AND users.type IN ('sp', 'PE', 'consultant') AND users.status
!= 'DA' AND users.usertype = '' AND emp_list.username='".$username."'";
	$chk_rs = mysql_query($chk_query,$db);
	if(mysql_num_rows($chk_rs)>0)
	{
		return true;
	}
	else
	{
		return false;
	}
}

//sendMail Function for ESS/CSS Users
function sendMailESSCSS($emailCSS,$css_matter,$cssSerialNo) {

	global $fromaddcoll, $toaddcoll, $ccaddcoll, $maildb, $db, $username, $smtp, $akken_arec_port, $akken_arec_mail, $egdomain;

	$sqlPriEmail 	= "SELECT fname, lname, email from hrcon_general where username = '".$username."' AND ustatus = 'active'";
	$resPriEmail 	= mysql_query($sqlPriEmail);
	$rowPriEmail	= mysql_fetch_assoc($resPriEmail);
	$priName		= $rowPriEmail['fname'].' '.$rowPriEmail['lname'];
	$priEmail		= $rowPriEmail['email'];
	$emailDet 		= $priName.' <'.$priEmail.'>';

	$curtime_header = date("D, j M Y H:i:s O");
	$to_array	= NULL;
	$to_array	= array($emailCSS);
	
	$css_subject		= "Self Service Portal Details";
	$username_temp_set	= "YES";
		
	$mailtype		= "text/html";
	$mailheaders	= array("Date: $curtime_header","From: $priName <$priEmail>","To: $emailCSS","Subject: $css_subject","MIME-Version: 1.0");
	$msg_body		= prepareBody($css_matter,$mailheaders,$mailtype);

	$smtp->host_name = $akken_arec_mail.".".$egdomain;
	$smtp->host_port = $akken_arec_port;

	$smtp->ssl		= 0;
	$smtp->pop3_auth_host	= "";
	$smtp->pop3_auth_port	= "";
	$smtp->user			= "";
	$smtp->password		= "";

	if($smtp->SendMessage($priEmail,$to_array,$mailheaders,$msg_body) ? "true" : "false")
    {
		addCSSESSActivity($cssSerialNo, $css_subject, $css_matter, $mailtype, $emailDet, $emailCSS, $username);
	}
}

function addCSSESSActivity($cssSerialNo, $css_subject, $css_matter, $acctype, $fromadd, $toadd, $username)
{
	$curtime_header=date("D, j M Y H:i:s O");
	global $username,$maildb,$db,$emailIdA,$inlinemailid,$msgs,$contactmail,$CharSet_mail;

	$ecEmail = "INSERT INTO contact_email(sno, username, contactsno, subject, fromadd, toadd, date, inlineid, type, sdate, charset)  
				VALUES ('', '".$username."', '".$cssSerialNo."', '".addslashes($css_subject)."', '".addslashes($fromadd)."', 
				'".addslashes($toadd)."', '".$curtime_header."', '0', '".addslashes($acctype)."', NOW(), '".addslashes($CharSet_mail)."')";
	mysql_query($ecEmail,$db);		
	$emailIdA	= mysql_insert_id($db);

	$ecCntEmail = "INSERT INTO contact_email_body (id, body) VALUES ('".$emailIdA."', '".mysql_real_escape_string($css_matter)."')";
	mysql_query($ecCntEmail,$maildb);
	$eid	= mysql_insert_id($maildb);
	
	$ecCmn = "INSERT INTO cmngmt_pr  (sno, con_id, username, tysno, title, sdate, subject,lmuser) 
				VALUES ('', '".$cssSerialNo."', '".$username."', '".$emailIdA."', 'Email',Now(), '".addslashes($css_subject)."', 
				'".$username."')";
	mysql_query($ecCmn,$db);
}

//Removes the smart quotes (content copied from external sources)

function remove_smart_quotes_from_string($str)
{

	$str = str_replace(
	array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
	array("'", "'", '"', '"', '-', '--', '...'),
	$str);
	// Replace Windows-1252 equivalents.
	$str = str_replace(
	array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
	array("'", "'", '"', '"', '-', '--', '...'),
	$str);
	$str =	stripslashes($str);
	return $str;
}

//Function to get the timesheet rules enabled or not
function getTimesheetRulesStatus()
{
	global $db;
	$ts_rules_status = false;
	$trque="SELECT COUNT(1),css_notifications FROM ts_rules WHERE status='ACTIVE'";
	$trres=mysql_query($trque,$db);
	$trrow=mysql_fetch_row($trres);
	if(DEFAULT_TSR=="Y" && ($trrow[0]>0 && $trrow[1]=="Y"))
	{
		$ts_rules_status = true;
	}
	return $ts_rules_status;
}

//Function to build the selected dates format
function get_service_dates_str($servicedate,$servicedateto){
	
	$servicedate_arr = explode('/',$servicedate);
	$servicedateto_arr = explode('/',$servicedateto);
	
	$servicedatenew = date_create($servicedate_arr[2]."-".$servicedate_arr[0]."-".$servicedate_arr[1]);
	$servicedatetonew = date_create($servicedateto_arr[2]."-".$servicedateto_arr[0]."-".$servicedateto_arr[1]);
	
	$servicedate_month 	= date_format($servicedatenew,'M');
	$servicedate_day	= date_format($servicedatenew,'d');
	$servicedate_year	= date_format($servicedatenew,'Y');
	
	$servicedateto_month 	= date_format($servicedatetonew,'M');
	$servicedateto_day	= date_format($servicedatetonew,'d');
	$servicedateto_year	= date_format($servicedatetonew,'Y');
	
	if($servicedate_year == $servicedateto_year){
		$service_dates_str = $servicedate_month." ".$servicedate_day;
		$service_dates_str .= " - ".$servicedateto_month." ".$servicedateto_day;
		$service_dates_str .= ", ".$servicedateto_year;
	}
	else{
		$service_dates_str = $servicedate_month." ".$servicedate_day;
		$service_dates_str .= ", ".$servicedate_year;
		$service_dates_str .= " - ".$servicedateto_month." ".$servicedateto_day;
		$service_dates_str .= ", ".$servicedateto_year;
		
	}
	return $service_dates_str;
}

//Function to send an email if password is updated successfully.
function mail_updPswd_suc($to_user_id,$chgdpswd_user_name)
{
	global $companyname,$db;
	
	$hrcon_que = 'select email from hrcon_general where username="'.$to_user_id.'"';
	$hrcon_res = mysql_query($hrcon_que,$db);
	if(mysql_num_rows($hrcon_res) >0)
	{
		$hrcon_row = mysql_fetch_row($hrcon_res);
		$to_user_emailid = $hrcon_row[0];
	}
	
	if($to_user_emailid != '')
	{
		$email_to = $to_user_emailid;
		$email_subject = 'Password Changed';
		$email_headers = "From : donot-reply@akken.com";
		$email_body = 'Hi '.ucwords($chgdpswd_user_name).',
	
Your password has been changed successfully.

With Regards,
'.$companyname.' Team.';

		@mail($email_to,$email_subject,$email_body,$email_headers);
	}
}

// Akkupay changes for Company Contributions tab to retrieve Contribution Periods
function getCompContributionPeriods($compContribPeriods){
	global $maindb;

	$contribPeriods=explode(",",$compContribPeriods);

	$compcontrib_Periods="<select style='width: 250px;' id=akku_contribperiod multiple=multiple>";
	$ique="SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='akku_contrib_period' order by sno ASC";
	$ires=mysql_query($ique,$maindb);
	while($irow=mysql_fetch_row($ires))
	{
		if(in_array($irow[0],$contribPeriods))
			$compcontrib_Periods.="<option selected value='".$irow[1]."'>".$irow[0]."</option>";
		else
			$compcontrib_Periods.="<option value='".$irow[1]."'>".$irow[0]."</option>";
	}
	$compcontrib_Periods.="</select>";

	return $compcontrib_Periods;
}
// Akkupay changes for Company Contributions tab to retrieve Contributions calculation methods
function getContributionCalcMethods($contrib_method = null){
    
    global $maindb;
        $contrib_method_option="";
	if($contrib_method == null || $contrib_method == '' )
	{
		$contrib_methodQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='contrib_calc_method' order by sno ASC ";
		$Response = mysql_query($contrib_methodQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        $contrib_method_option.='<option  value="'. $Row[1] .'">'. $Row[0] . '</option>';  
                }
	}else{
		  
		$contrib_methodQuery = "SELECT name,custom_name FROM mphr_manage WHERE status='Y' AND type='contrib_calc_method' order by sno ASC ";
		$Response = mysql_query($contrib_methodQuery, $maindb);
                while ($Row = mysql_fetch_row($Response)) 
                {
                        if($Row[1]==$contrib_method){ 
                                $contrib_method_option.='<option  value="'. $Row[1] .'" selected >'. $Row[0] . '</option>';
                        }else{
                                $contrib_method_option.='<option  value="'. $Row[1] .'" >'. $Row[0] . '</option>';
                        }
                }
        }
        
    return $contrib_method_option;
       
}

// Akkupay changes for Company Contributions tab to retrieve Contributions codes of Type='CN'
function getCompContributionCodes($contribcodesno = null){
    
    global $db;
    $contribcode_option="";
    if($contribcodesno == null || $contribcodesno == '' )
    {
        $contribcodeQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='CN' order by sno ASC ";
        $Response = mysql_query($contribcodeQuery,$db);
        while ($Row = mysql_fetch_row($Response)) 
        {
                $contribcode_option.='<option  value="'. $Row[0] .'">'. $Row[2] . '</option>';  
        }
    }
    else
    {
		  
        $contribcodeQuery = "SELECT sno,type,code,description FROM mphr_codes WHERE status='Y' AND type='CN' order by sno ASC ";
        $Response = mysql_query($contribcodeQuery, $db);
        while ($Row = mysql_fetch_row($Response)){ 
            if($Row[0]==$contribcodesno){ 
                $contribcode_option.='<option  value="'. $Row[0] .'" selected >'. $Row[2] . '</option>';
            }else{
                $contribcode_option.='<option  value="'. $Row[0] .'" >'. $Row[2] . '</option>';
            }
        }
    }
   return $contribcode_option;   
}

//Display The schedule shift times
function display_Shift_Times($time='')
{

	 $start=strtotime('00:15');
	 $end=strtotime('23:45');
	 $selTime = "";
	 if($time=="24:00")
	 {
	 	$selTime = "selected=selected";
	 }
	 $Timeoptions = "<option value='24:00' $selTime>12:00am</option>";

	for ($i=$start;$i<=$end;$i = $i + 15*60)
    { 
    	$sel_time = date('G:i',$i);
    	$selected = "";
    	if($sel_time==$time)
    	{
    		$selected = "selected=selected";
    	}
		$Timeoptions.="<option value='".date('G:i',$i)."' $selected>".date('g:ia',$i)."</option>";
	}
	return $Timeoptions;
}

// To replace all invalid char's 
function replace_invalid_utf8($input)
{
	//--------Replacing special char's like ’,” with single quote------START
	$input=str_replace("’","'",$input);
	$input=str_replace("‘","'",$input);
	$input=str_replace('”',"'",$input);
	$input=str_replace('“',"'",$input);
	$input=str_replace("–","-",$input);
	$input=stripslashes($input);
	$input=str_replace('\\', '',$input);
	//-------------END-------------------
	
	$input=preg_replace("/(\n|\t|\r|\b|\f)*/","",$input);
	//$input=html_tls_specialchars(trim($input),ENT_QUOTES); Removed due to "&amp;#039;" is syncing instead of "'"	
	return mb_convert_encoding($input, 'UTF-8', 'UTF-8');
}

function sendMailUserCreation($toEmail,$mailMatter,$userSerialNo,$mailSubject,$activityMatter) 
{
	global $fromaddcoll, $toaddcoll, $ccaddcoll, $maildb, $db, $username, $smtp, $akken_arec_port, $akken_arec_mail, $egdomain;

	$sqlPriEmail 	= "SELECT fname, lname, email from hrcon_general where username = '".$username."' AND ustatus = 'active'";
	$resPriEmail 	= mysql_query($sqlPriEmail);
	$rowPriEmail	= mysql_fetch_assoc($resPriEmail);
	$priName		= $rowPriEmail['fname'].' '.$rowPriEmail['lname'];
	$priEmail		= $rowPriEmail['email'];
	$emailDet 		= $priName.' <'.$priEmail.'>';

	$mailSubject = stripslashes($mailSubject);

	$curtime_header = date("D, j M Y H:i:s O");
	$to_array	= NULL;
	$to_array	= array($toEmail);
	
	//$css_subject		= "Self Service Portal Details";
	$username_temp_set	= "YES";
		
	$mailtype		= "text/html";
	$mailheaders	= array("Date: $curtime_header","From: $priName <$priEmail>","To: $toEmail","Subject: $mailSubject","MIME-Version: 1.0");
	$msg_body		= prepareBody($mailMatter,$mailheaders,$mailtype);

	$smtp->host_name = $akken_arec_mail.".".$egdomain;
	//$smtp->host_name = $egdomain;
	$smtp->host_port = $akken_arec_port;

	$smtp->ssl		= 0;
	$smtp->pop3_auth_host	= "";
	$smtp->pop3_auth_port	= "";
	$smtp->user			= "";
	$smtp->password		= "";

	$smtpRes = $smtp->SendMessage($priEmail,$to_array,$mailheaders,$msg_body); 

	if($smtpRes == 1)
    {
		addCSSESSActivity($userSerialNo, $mailSubject, $activityMatter, $mailtype, $emailDet, $toEmail, $username);
	}
}

function generateUserNameForUserMngmt($lname,$fname) 
{
	if($fname != '' && $lname == '') 
	{
		$temp_user	= trim(strtolower($fname));
		$namelen 	= strlen($temp_user);
		$randomVal 	= mt_rand(10000,99999);
		if($namelen <= 5) 
		{
			$user_list = $temp_user.$randomVal;
		}
		else
		{
			$user_list = $temp_user;
		}
	}
	elseif($fname == '' && $lname == '') 
	{
		$randomVal 	= mt_rand(100000,999999);
		$user_list = $randomVal;
	}
	elseif($fname != '' && $lname != '') 
	{
		$randomVal 	= mt_rand(10000,99999);
		$temp_user	= strlen(trim(strtolower($lname)));

		if($temp_user < 5) 
		{
			$conName = strtolower($fname).strtolower($lname);
			if(strlen($conName) < 6) 
			{
				$user_list = $conName.$randomVal;
			}
			else 
			{
				$user_list = $conName;
			}
		}
		else 
		{
			$tempName = substr($fname,0,1).$lname;
			$user_list = trim(str_replace(" ","",strtolower($tempName)));
		}
	}
	elseif($fname == '' && $lname != '') 
	{
		$randomVal = mt_rand(100000,999999);
		if(strlen(trim(strtolower($lname))) < 6) 
		{
			$user_list = trim(strtolower($lname)).$randomVal;
		}
		else 
		{
			$user_list = trim(strtolower($lname));
		}
	}
	return $user_list;
}

function getCompanyNameForUserMngmt() 
{
	global $maindb,$db;

	$sqlCompany = "SELECT company_name FROM company_info LIMIT 0,1";
	$resCompany = mysql_query($sqlCompany,$db);
	$rowCompany	= mysql_fetch_assoc($resCompany);
	
	return $rowCompany['company_name'];
}

function encrypt_decrypt($action, $string) 
{
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'ujFMDNux';
    $secret_iv = 'UtbjPfTk';
    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) 
    {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } 
    else if( $action == 'decrypt' ) 
    {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    if ($output == false) {
    	throw new Exception("URL In-Valid.");
    }
    return $output;
}
function is_ascii($string)
{
    return !preg_match('/[^\\x00-\\x7F]/S', $string);
}
function clean_string($str)
    {
        if (is_ascii($str) === FALSE)
        {
            $str = !mb_detect_encoding($str, 'UTF-8', TRUE) ? iconv('Windows-1252', 'UTF-8', $str) : $str;
        }

        return $str;
}

function format_ssn($ssn,$hide=true)
{
	$ssnVal = '';
	if ($ssn !="") {

		$ssn = str_replace('-','',$ssn);
		if ($hide) {
			$ssnVal = 'XXX-XX-'.substr($ssn,5);
		}else{
			$ssnVal = substr($ssn, 0, 3).'-'.substr($ssn, 3, 2).'-'.substr($ssn,5);
		}
	}
    return $ssnVal; 
    
}

//$odate is original format of date stored in db,$nformat is new format we want
function get_standard_dateFormat($date, $oformat, $nformat='m/d/Y'){ 
 	$converted_date ='';
 	if ($date != "0-0-0" && $date != "00-00-0000" && $date !="" && $date != "00/00/0000") {
    	$odate = date_create_from_format($oformat, $date); // Your original format
	
    	$converted_date = date_format($odate, $nformat); // choose format do you like
	}
	return $converted_date;
} 

//Function to update hrcon_* fields added in emp_list table and removed individual emp_list update statements.
function emp_list_update($empusername){
	global $db;
	$query  =  "UPDATE emp_list e,
		hrcon_general hg,
		hrcon_personal hp,
		hrcon_w4 hw,
		hrcon_compen hc
		
		SET
		e.name=TRIM(CONCAT('',TRIM(hg.fname),' ',IF(hg.mname='','',CONCAT(hg.mname,' ')),hg.lname)),
		e.email=hg.email,
		e.address1=hg.address1,
		e.address2=hg.address2,
		e.city=hg.city,
		e.state=hg.state,
		e.stateid=hg.stateid,
		e.coutnry=hg.country,
		e.primary_phone=hg.wphone,
		e.primary_phone_ext=hg.wphone_extn,
		e.secondry_phone=hg.hphone,
		e.secondry_phone_ext=hg.hphone_extn,
		e.other_phone=hg.mobile,
		e.emp_fname=hg.fname,
		e.emp_mname=hg.mname,
		e.emp_lname=hg.lname,
		e.emp_ssn=hp.ssn,
		e.emp_ssn_hash = hp.ssn_hash,
		e.emp_tax=hw.tax,
		e.emp_locid=hc.location,
		e.emp_department=hc.dept,
		e.emp_date_hire=hc.date_hire,
		e.emp_timesheet=hc.timesheet,
		e.pobstatus = e.pobstatus,
		e.ds_pobstatus = e.ds_pobstatus,
		e.wotc_mja_status = e.wotc_mja_status,
		e.mtime = NOW()

		WHERE
		e.username=hg.username AND hg.ustatus='active'
		AND e.username=hp.username AND hp.ustatus='active'
		AND e.username=hw.username AND hw.ustatus='active'
		AND e.username=hc.username AND hc.ustatus='active'
		
		AND e.username='".$empusername."' ";

	mysql_query($query,$db);

	//Updating emp_list jtype (Left join - previously on grid we used to get it from hrcon_jobs not from hrcon_compen if we directly use hrcon_compen jtype then placed candidate record will always shows On Bench)
	$query  =  "UPDATE emp_list e 
				LEFT JOIN hrcon_jobs hj ON (e.username=hj.username AND hj.ustatus='active')
				SET e.emp_jtype=hj.jtype, e.cur_project = hj.project, e.rate = hj.pamount  WHERE  
				hj.username= '".$empusername."' ";
	mysql_query($query,$db);

}


function getUDFValues($udf_mod, $rec_id)
{
	$udf_cols = array();		
	$udf_sel_udf_cols = "select element_lable as col from udf_form_details t1 left join udf_form_details_order t4 on t4.custom_form_details_id = t1.id
			where t1.module  = ".$udf_mod." and t1.status = 'Active' order by t4.ele_order asc";
	$res_udf_cols = mysql_query($udf_sel_udf_cols);
		
		while($rec_udf_cols = mysql_fetch_array($res_udf_cols))
		{
		    $udf_cols[] = $rec_udf_cols['col'];
		}
		
		$udf_cols = implode(",", $udf_cols);
		switch($udf_mod)
		{
			case 3:
				
				$udf_sel_udfvalues = "select ".$udf_cols." from udf_form_details_candidate_values where rec_id = ".$rec_id;
				$res_udfvalues = mysql_query($udf_sel_udfvalues);
				$rec_udfvalues = mysql_fetch_row($res_udfvalues);
				$udf_values = implode("|",$rec_udfvalues);
				break;
			
	}
	return $udf_values;		
}
?>
