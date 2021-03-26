<?php
	require("global.inc");
	require("Menu.inc");
	require_once($akken_psos_include_path.'commonfuns.inc');
	$menu=new EmpMenu();
	//$menu->showHeader1("accounting","Time&nbsp;Sheets","1");
	// Timesheet Class
	global $db;
	require_once('timesheet/class.Timesheet.php');
	$timesheetObj = new AkkenTimesheet($db);
	$RateTypes = $timesheetObj->getRateTypes();

	$defaultHeaders = array(' ', 'Assignments');
	if(MANAGE_CLASSES == 'Y')
	{
		array_push($defaultHeaders, 'Class');
	} else {
		array_push($defaultHeaders, '');
	}
	//array_push($defaultHeaders, 'Total');
	$BuildHeaders = $timesheetObj->buildDynamicHeaders_multi($defaultHeaders);					
	
	$efrom1 = getFromEmailID($username);
	$aryClasses = getClassesSetups();
	function sel($a,$b)
	{
		if($a==$b)
			return "selected";
		else
			return "";
	}

	function chk($a,$b)
	{
		if($a==$b)
			return "checked";
		else
			return "";
	}
	if($servicedatefrom == '' || $servicedateto == '')
	{
		
		$startEndDates = getStartEndDatesBasedOnWeekendDay();
		$servicedatefrom = $startEndDates['StartDate'];		
		$servicedateto = $startEndDates['EndDate'];		
	}
	$from_date = explode("/",$servicedatefrom);								
	$checking_from = $from_date[2]."-".$from_date[0]."-".$from_date[1];
	
	$to_date = explode("/",$servicedateto);								
	$checking_to = $to_date[2]."-".$to_date[0]."-".$to_date[1];

	if($empnames=="")
	{
		$query1 = "SELECT timesheet FROM hrcon_compen WHERE ustatus != 'backup' and username ='".$username."'";
		$result1 = mysql_query($query1,$db);
		$row1 = mysql_fetch_row($result1);
		$queryin = "SELECT lstatus FROM emp_list WHERE username='".$username."'";
		$resin = mysql_query($queryin,$db);
		$rowin = mysql_fetch_row($resin);
		$sql_LJS = "select pusername,client,ustatus,date(str_to_date(e_date,'%m-%d-%Y')) from hrcon_jobs where username = '" . $username . "' AND hrcon_jobs.jtype != '' and ustatus IN( 'active','closed','cancel') order by udate";
		$ds_LJS  = mysql_query($sql_LJS, $db);
		//$counthrcon = mysql_num_rows($ds_LJS);
		
		$getmaxdate = "SELECT MAX(edate) FROM par_timesheet WHERE username='".$username."'";
		$maxres=mysql_query($getmaxdate,$db);
		$maxdaterow=mysql_fetch_row($maxres);
		
		$counthrcon = 0;
		while($countrows = mysql_fetch_row($ds_LJS))
		{
			$dateFlag = true;
			if($countrows[2]!='active')
			{
				if($countrows[3]>=$checking_from && $countrows[3]!='' && $countrows[3]!='0-0-0')
					$dateFlag = true;
				else 
					$dateFlag = false;
			}
			if($dateFlag)
			{
				$counthrcon = $counthrcon+1;
			}
		}
		
		if($row1[0] != 'Y' && $rowin[0] != 'INACTIVE' && $rowin[0] != '' && $counthrcon>0)
			$new_user=$username;
		else
		{
			$query="SELECT emp_list.username, emp_list.name
					  FROM emp_list, hrcon_jobs
					WHERE emp_list.username = hrcon_jobs.username
					       AND emp_list.lstatus != 'DA'
					       AND emp_list.lstatus != 'INACTIVE'
					       AND (   emp_list.empterminated != 'Y'
					            || (  UNIX_TIMESTAMP(DATE_FORMAT(emp_list.tdate, '%Y-%m-%d'))
					                - UNIX_TIMESTAMP()) > 0)
					       AND hrcon_jobs.ustatus IN ('active', 'closed', 'cancel')
					       AND hrcon_jobs.jtype != ''
					       AND emp_list.emp_timesheet != 'Y'
					GROUP BY emp_list.username
					ORDER BY emp_list.name";
			$result=mysql_query($query,$db);
			while($roww = mysql_fetch_array($result))
			{
				$getmaxdate = "SELECT MAX(edate) FROM par_timesheet WHERE username='".$roww[0]."'";
				$maxres=mysql_query($getmaxdate,$db);
				$maxdaterow=mysql_fetch_row($maxres);
			
				$dynamicUstatus = " AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y')) <= '".$checking_to."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$checking_from."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$checking_from."'),1)))";
				$getActiveAssignments = "select count(1) from hrcon_jobs where username = '".$roww[0]."' and pusername!=''".$dynamicUstatus." order by udate";
				$activeRes=mysql_query($getActiveAssignments,$db);
				$activeCount=mysql_fetch_row($activeRes);
				
				$dateFlag = false;
				if($activeCount[0] > 0)
				{
					$dateFlag = true;
				}
				if($dateFlag)
				{
					$new_user=$roww[0];
					break;
				}
			}
		}	
	}
	else
		$new_user=$empnames;
	
	$dynamicUstatus = " AND hrcon_jobs.jtype!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y')) <= '".$checking_to."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$checking_from."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$checking_from."'),1)))";

	//query for getting employee list for to fill time sheet 
	$query_total="SELECT emp_list.username uid, count(hrcon_jobs.assign_no) AS totalasgn
				  FROM emp_list,
				       hrcon_jobs
				 WHERE     emp_list.username = hrcon_jobs.username
				       AND emp_list.lstatus != 'DA'
				       AND emp_list.lstatus != 'INACTIVE'
				       AND (   emp_list.empterminated != 'Y'
				            || (  UNIX_TIMESTAMP(DATE_FORMAT(emp_list.tdate, '%Y-%m-%d'))
				                - UNIX_TIMESTAMP()) > 0)
				       AND hrcon_jobs.ustatus IN ('active', 'closed', 'cancel')
				       AND hrcon_jobs.ustatus != ''
				       AND hrcon_jobs.jtype != ''
				       AND emp_list.emp_timesheet != 'Y' ".$dynamicUstatus."
	GROUP BY emp_list.username, emp_list.name
	ORDER BY trim(emp_list.emp_lname),trim(emp_list.emp_fname),trim(emp_list.emp_mname)";
	$result_total=mysql_query($query_total,$db);
	
	while($myrow_total=mysql_fetch_array($result_total))
	{
		$totalAsgnArr[$myrow_total[0]] = $myrow_total[1];
	}
	
	//query for getting employee list for to fill time sheet 
		
	
	// appending employee usernames to timedata which are from search popup window

	if($search_emp != '')
	{
		$search_empArray_com = explode("|",$search_emp);

		for($k = 0; $k < count($search_empArray_com); $k++)
		{
		  $search_empArray_comArry[$k] = explode("_",$search_empArray_com[$k]);	
		  
		}

		for($j = 0; $j < count($search_empArray_comArry); $j++)
		{
			$search_empArray[$j]	= $search_empArray_comArry[$j][0];
			$search_compArray[$j]	= $search_empArray_comArry[$j][1];
			$search_asgnArray[$j]	= $search_empArray_comArry[$j][2];
		}

		$count_search_emp = count($search_empArray);
		for($i = 0; $i < $count_search_emp; $i++)
		{
			if($timedata == '')
				$timedata = $search_empArray[$i]."_".$search_compArray[$i].'_'.$search_asgnArray[$i]."|||||||||";//added extra pipe symbol for quick books value
			else
				$timedata = $timedata."^".$search_empArray[$i]."_".$search_compArray[$i].'_'.$search_asgnArray[$i]."|||||||||";//added extra pipe symbol for quick books value
		}
	}

	$numberOfEmployeeds = 0;
	$empValArray = array();
	$search_emp = '';
	if(strlen($timedata)>0)
	{
		$sintimebeforetest=explode("^",$timedata);
		$j=-1;
		for($i=0;$i<count($sintimebeforetest);$i++)
		{
		   $elementsbefore_comtest1[$i]=explode("|",$sintimebeforetest[$i]);
		   $id = explode("_",$elementsbefore_comtest1[$i][0]);
		   $elementsbefore_comtestu[$i] = $id[0];
		}
		
		
		$elementsbefore_comtestu = array_keys(array_count_values($elementsbefore_comtestu));
		$ids = implode("','",$elementsbefore_comtestu);
		$ids = "'".$ids."'";
		
		if($sortby == 'lname')
		$getsq = "SELECT emp_list.username FROM emp_list, hrcon_general WHERE hrcon_general.username = emp_list.username AND hrcon_general.ustatus IN ('active','ACTIVE') AND emp_list.username IN (".$ids.") order by hrcon_general.lname,hrcon_general.fname";
		else if($sortby == 'fname')
		$getsq = "SELECT emp_list.username FROM emp_list, hrcon_general WHERE hrcon_general.username = emp_list.username AND hrcon_general.ustatus IN ('active','ACTIVE') AND emp_list.username IN (".$ids.") order by hrcon_general.fname,hrcon_general.lname";
		else if($sortby == 'sno')
		$getsq = "SELECT emp_list.username FROM emp_list, hrcon_general WHERE hrcon_general.username = emp_list.username AND hrcon_general.ustatus IN ('active','ACTIVE') AND emp_list.username IN (".$ids.") order by emp_list.sno";
		else
		$getsq = "SELECT emp_list.username FROM emp_list, hrcon_general WHERE hrcon_general.username = emp_list.username AND hrcon_general.ustatus IN ('active','ACTIVE') AND emp_list.username IN (".$ids.") order by hrcon_general.lname,hrcon_general.fname";

		$resq = mysql_query($getsq);
		$p=0;
		while($rea = mysql_fetch_array($resq))
		{
		$elementsbefore_comtest[$p] =  $rea[0];
		 for($i1=0;$i1<count($sintimebeforetest);$i1++)
		   {
		    $arr1 = explode('|',$sintimebeforetest[$i1]);
		    $arr = explode("_",$arr1[0]); 
		   if(trim($arr[0]) == trim($elementsbefore_comtest[$p]))
		   {
		       $j++;
		         $sintimebefore[$j] = $sintimebeforetest[$i1];
				
		    }
		  }
		$p++;
		
		}
		
	
		$rowcoubefore = count($sintimebefore);
		$sintimeafter = array();
		for($i=0;$i<$rowcoubefore;$i++)
		{
		    $elementsbefore_com[$i]=explode("|",$sintimebefore[$i]);
		 	$elementsbefore_arr[$i]=explode("_",$elementsbefore_com[$i][0]);
			$elementsbefore[$i]=$elementsbefore_arr[$i][0];	
            $elementsbefore1[$i]=$elementsbefore_arr[$i][1];
				
$dynamicUstatus = " AND hrcon_jobs.jtype!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y')) <= '".$checking_to."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$checking_from."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$checking_from."'),1)))";
			
			$getActiveAssignments = "select count(1),CONCAT_WS(' ',hrcon_general.lname,hrcon_general.fname,hrcon_general.mname,hrcon_general.username) from hrcon_jobs,  hrcon_general where hrcon_general.username = hrcon_jobs.username AND hrcon_general.ustatus IN ('active','ACTIVE') AND hrcon_jobs.username = '".$elementsbefore[$i]."' and  hrcon_jobs.pusername!=''".$dynamicUstatus."  GROUP BY hrcon_jobs.username";
			
			$activeRes=mysql_query($getActiveAssignments,$db);
			$activeCount=mysql_fetch_row($activeRes);
			if($activeCount[0] > 0)
			$sintimeafter[strtolower($activeCount[1]).$i] = $sintimebefore[$i];
			if(!in_array($activeCount[1],$empValArray))
				$empValArray[] = $activeCount[1];
		}	

		//ksort($sintimeafter);	
		$sintime = array_values($sintimeafter);	
		$rowcou = count($sintime);
		for($i=0;$i<$rowcou;$i++)
		{
		 	$elements[$i]=explode("|",$sintime[$i]);			
		}
	}
	
/* 	echo "<pre>";
		$req_Str = explode('^',$_REQUEST['timedata']);
		print_r($req_Str);
		for($req_int=0; $req_int<=count($req_Str); $req_int++){
		
			$req_data = explode('|',$req_Str[$req_int]);
			print_r($req_data);
		
		}
	
	*/
//echo "<pre>";
//	print_r($elements);
//echo "</pre>";
//echo "from date is ".$checking_from."<br>";
//echo "from date is ".$checking_to."<br>";
	
	$numberOfEmployeeds = count($empValArray);
$divStyle = (MANAGE_CLASSES == "Y") ? '' : 'style="display:none"';
?>
<title> Create Timesheet</title>
<div id='dynsndiv' style='display:none;'></div>
	<script src="/BSOS/scripts/jquery-1.8.3.js"></script>
	<script type="text/javascript" src="/BSOS/scripts/getBrowserInfo.js"></script>
    <link href="/BSOS/css/fontawesome.css" rel="stylesheet" type="text/css">
    <link href="/BSOS/Home/style_screen.css" rel="stylesheet" type="text/css">
	<link href="/BSOS/css/educeit.css" rel="stylesheet" type="text/css">
	<link href="/BSOS/css/grid.css" rel="stylesheet" type="text/css">
	<link href="/BSOS/css/filter.css" rel="stylesheet" type="text/css">
	<link href="/BSOS/css/xtree.css" rel="stylesheet" type="text/css">
	<link href="/BSOS/css/tab.css" rel="stylesheet" type="text/css">
	<link href="/BSOS/css/site.css" type=text/css rel=stylesheet>
	<link href="/BSOS/css/style1.css" rel="stylesheet" type="text/css">
	<link href="/BSOS/css/dropmenu.css" rel="stylesheet" type="text/css">
	<link href="/BSOS/css/calendar.css" rel="stylesheet" type="text/css">

	<script src="/BSOS/scripts/cookies.js"></script>
	<script src="/BSOS/scripts/dropmenu.js"></script>
	<script src="/BSOS/scripts/tabpane.js"></script>
	<script src="/BSOS/scripts/grid.js"></script>
	<script src="/BSOS/scripts/xtree.js"></script>
	<script src="/BSOS/scripts/filter.js"></script>
	<script src="/BSOS/scripts/menu.js"></script>
	<script src="/BSOS/scripts/paging.js"></script>
	<script src="/BSOS/scripts/preferences.js" language=javascript></script>

	<script src="/BSOS/scripts/downloadarray.js"></script>
	<script src="/BSOS/scripts/newfeaturedownmenu.js"></script>
	<script language="javascript" src=/BSOS/scripts/validatetimefax.js></script>   
   <link type="text/css" rel="stylesheet" href="/BSOS/css/timesheet.css">	
	<script src=/BSOS/scripts/date_format.js language=javascript></script>
	<style>
.txtelement {color: #474c4f;}
.time-alert-button {
	border:#5A5656 1px solid;
	font-size: 12px;
	color:#FFFFFF;
	font-family: arial;
	background-color: #5A5656;
	text-align:center;

	height:20px;
	font-weight:bold;
	padding:0 .50em;
	width:auto;
	overflow:visible;
}
.alert-time-msg {
	margin-top:30px;
	font:12px Arial, Helvetica, sans-serif;
	text-align:left;
	padding-left:15px;
	padding-right:15px;
	padding-bottom: 10px;
}
.drpdwnaccc {    
    color:#474c4f;
    font-family: Arial;
    font-size: 11px;
}
.afontstylee {
    color:#474c4f;
    font-family: Arial;
    font-size: 12px;
    font-style: normal;
  	padding-left:7px;	
}

.drpdwnaccemp {
    background-color: #FFFFFF;
    color: #474c4f;
    font-family: Arial;
    font-size: 11px;
    width: 180px;
}
/* added for gaps issue in timesheet */
#MainTable td{
	white-space: nowrap;
}
#SaveAlert{
	top: 95px !important;
}
.textwrampnew { white-space: normal !important}
.addtaskBtn{ width:135px !important; }
.fa.fa-calendar {margin-left: 5px;}
.TimesheetNewbg{background-color: #f6f7f9 !important;}
@media screen and (-webkit-min-device-pixel-ratio:0) {
    ::i-block-chrome, .textwrampnewSafari{ white-space: inherit  !important}
}
.TimeSheetContM{
	margin-top:0px !important;
}
.titleNewPad{ padding:10px;}
.multiTimeSticky{ top:70px; position:fixed; background: #ffffff;border-bottom: 1px solid #cccccc;height:40px;  left: 0; padding: 10px 0; 
    width: 100%;z-index: 999;}
.searchEmpStycky{top:131px; position:fixed; width:100%;border-bottom: 1px solid #cccccc;height:40px;z-index: 999;background: #f6f7f9; padding-left:10px;padding-top:4px;}
</style>
<script type="text/javascript" src="/BSOS/scripts/calendar.js"></script>
<link rel="stylesheet" type="text/css" href="/BSOS/css/calendar.css">
<link rel="stylesheet" href="/BSOS/popupmessages/css/popup_message.css" media="screen" type="text/css">
<link rel="stylesheet" href="/BSOS/css/popup_styles.css" media="screen" type="text/css">
<link rel="stylesheet" href="/include/timesheet/css/timesheet.css" media="screen" type="text/css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/timeSheetselect2.css">
<!-- <script type="text/javascript" src="/BSOS/scripts/select2.js"></script> -->
<script type="text/javascript" src="/BSOS/scripts/select2_V4.0.3.js"></script>

<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/select2_V_4.0.3.css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/gigboardCustom.css">
<style type="text/css">
.container-chk{ margin-right:6px;}
div#tcal{ z-index:9999}
.multiAddBtn{ margin:0px 5px; padding:6px 10px;background:#3fb8ef; border-radius:4px; border:solid 1px #3fb8ef; color:#fff}
.multiAddBtn .fa-plus-square:before{ color:#fff;}
.multiAddBtn:hover{ background:#0eb1fb;}
</style>
	<form action=savetimemulti.php name=sheet method=post ENCTYPE="multipart/form-data">
	<input type=hidden id="efrom1" name="efrom1" value="<?php echo $efrom1; ?>">
	<input type=hidden name=aa id=aa value="">
	<input type=hidden name=rowcou value="<?php echo $rowcou;?>">
	<input type=hidden name=client id=client value="">
	<input type=hidden name=task id=task value="">
	<input type=hidden name=thours id=thours value="">
	<input type=hidden name=othours id=othours value="">
	<input type=hidden name=dbhours id=dbhour value="">
	<input type=hidden name=billable id=billable value="">
	<input type=hidden name=empusername id=empusername value="">	
	<input type=hidden name=val value="<?php echo $val;?>">
	<input type=hidden name=timedata value="<?php echo html_tls_specialchars(addslashes($timedata),ENT_QUOTES);?>">
	<input type=hidden id=ratesdata name=ratesdata value="">
	<input type=hidden name=module value="<?php echo $module;?>">
	<input type=hidden name=selfserClient value="<?php echo $ClVal;?>">	
	<input type=hidden name=checking_from value="<?php echo $servicedatefrom;?>">
	<input type=hidden name=checking_to value="<?php echo $servicedateto;?>">
	<input type=hidden name="search_emp" value="<?php echo $search_emp;?>"> 
	<input type=hidden name=sdates id=sdates value="">
	<input type=hidden name=edates id=edates value="">
	<input type=hidden name=status id=status value="">
	<input type=hidden name=sno_ts id=sno_ts value="">
	<input type=hidden name=auser id=auser value="">
	<input type=hidden name=jobtype id=jobtype value="">
	<input type=hidden name=qbid id=qbid value="">
    <input type="hidden" name=sortby id=sortby value="<?=$sortby?>">
	<input type=hidden name=ratecount id=ratecount value="<?php echo count($RateTypes); ?>">
	<input type=hidden name=hourstype id="hourstype" value=""  >
	<input type=hidden name=hours id="hours" value="" >
	<input type=hidden name=class_type id="class_type" value="">
	<input type=hidden name=chksnoid id=chksnoid value="">
	<input type="hidden" name="asgmt_id" id="asgmt_id" value="<?php echo $asgmt_id; ?>">
	<!-- values from search popup-->
	
<div id="main" style="outline: none;display: none; min-height:300px;">
<td valign=top align=center>
<table width=100% cellpadding="0" cellspacing="0" border=0 class="ProfileNewUI defaultTopRange" align="center">
	<div id="content">
		<tr>
        <td style="position:relative">
       <div class="CustTimeDateRangeT">
				<table width=99% cellpadding=0 cellspacing=0 border=0 class="ProfileNewUI SummaryTopBg">
					<tr>
                    <td><font class="modcaption CustTimeSheetHed">Create Timesheet</font></td>
					<td align=right><span class="FontSize-16">Create Timesheets From </span><span class="TMEDateBg"><input type=text size=10  maxlength="10" name=servicedatefrom id=servicedatefrom value="<?php echo $servicedatefrom;?>" tabindex="1"><script language='JavaScript'>new tcal ({'formname':window.form,'controlname':'servicedatefrom'});</script>&nbsp; &nbsp; To  &nbsp; &nbsp;<input type=text size=10  maxlength="10" name=servicedateto id=servicedateto value="<?php echo $servicedateto;?>" tabindex="2"><script language='JavaScript'>new tcal ({'formname':window.form,'controlname':'servicedateto'});</script>&nbsp;</span><span class="TMEDateViewBtn"><a href=javascript:DateCheck('servicedatefrom','servicedateto')> <i class="fa fa-eye fa-lg"></i> view</a></span></td>
					</tr>										
				</table>
                </div>
			</td>
		</tr>
	</div>
	<table width=100% cellpadding=0 cellspacing=0 border=0  align="center" class="ProfileNewUI defaultTopRange">
	 <div id="topheader" width="100%">
		<tr class="NewGridTopBg">
		<?php
			
		if($module == 'Accounting')
		{
			$name=explode("|","fa fa-times~Remove|fa fa-thumbs-o-up~Submit&nbsp;&&nbsp;Approve|fa fa-floppy-o~Submit|fa-ban~Cancel");
			$link=explode("|","javascript:deleteMulti('Remove')|javascript:showPreloader('approve')|javascript:showPreloader('submit')|javascript:self.close()");
		}		
		
		$heading="";
		$menu->showHeadingStrip1($name,$link,$heading,"left");
		
		?>
		</tr>
		<tr>
				<td  valign="middle"  class="TimesheetNewbg" style="position:relative">
                 <div class="searchEmpStycky">                
                <table border="0" width="100%" cellpadding="0" cellspacing="0">
                <tr>
				<td width="24%"><font  class="afontstyle"><a class="txtlink" href="javascript:SearchEmployeeWin()"><strong>Search and Select Employee List</strong>&nbsp; <i class="fa fa-search fa-lg"></i></a></font>
</td>
				<td valign="middle">&nbsp;&nbsp;&nbsp;&nbsp;<font class=lfontstyle4 style="vertical-align:middle;"><strong>OR</strong></font>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td valign="middle"><font style="vertical-align:middle" class=lfontstyle4>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Select an Employee:</strong>&nbsp;&nbsp;&nbsp;&nbsp;</font>

					<select class="drpdwnaccemp" id="empnames" name="empnames" style="width:200px !important;">
					</select>

					<span id='TodoAdd' class="multiAddBtn" style="cursor:pointer"  onclick="javascript:addNewEmployee()" ><i class="fa fa-plus-square"></i> Add</span></td><td valign="middle"> </td><td>&nbsp;</td><td align="left"><font style="vertical-align:middle" class=lfontstyle4><strong>No. of Employees selected : <?php echo $numberOfEmployeeds;?></strong></font></td></tr></table>
                
                </div>
                </td>
		</tr>
		</div>
	</table>	
		<?php		
		if($rowcou > 0)
		{
		$colspan = (count($RateTypes)+6);
		$Tablewidth = (count($RateTypes) * 9) + 60;
	    if($Tablewidth < 100)
	    $Tablewidth = 100;
		?>
		<tr>
			<td>
		<div id="grid_form" style="padding-top:89px;">		
				<table id="MainTable" cellspacing=0 cellPadding=2 width="100%" border=0 class="ProfileNewUI CustomTimesheetTh CustomTimesheetInput TimeSheetContM">
                     <tr>						
						<?php echo $BuildHeaders; ?>			
					</tr>
					<!--<tr>
					<td colspan="<?=$colspan?>">-->
			<?php
			$total=0;$dhours=0; $dmin=0;
			$total1=0;$dhours1=0; $dmin1=0;
			$total2=0;$dhours2=0; $dmin2=0;
			$tab_index=3;
			$rowcouval = $rowcou;
				for($r=0;$r<$rowcou;$r++)
				{
					$rowid = $r+1;
					$tab_index_task = $tab_index+5;
						if($r != 0)
							$tab_index = $tab_index++;
							
						if($r%2==0)
							$class="tr1bgcolor";
						else
							$class="tr2bgcolor";

						$empArr		= explode("_",$elements[$r][0]);
						$empfetchdata	= $empArr[0];
						$compfetchdata	= $empArr[1];
						$selasgnmtdata	= $empArr[2];

							$getEmpName = "SELECT emp_list.name,".getEntityDispName("emp_list.sno","CONCAT_WS(' ',hrcon_general.lname,hrcon_general.fname,hrcon_general.mname)",3).", emp_list.username FROM emp_list, hrcon_general WHERE hrcon_general.username = emp_list.username AND hrcon_general.ustatus IN ('active','ACTIVE') AND emp_list.username ='".$empfetchdata."' ";
							$resEmpName = mysql_query($getEmpName,$db);
							$rowEmpName = mysql_fetch_row($resEmpName);
						?>
						<!--<table border="0" width="100%" cellpadding="6" cellspacing="0">-->
						
						<tr class=<?php echo $class;?>>
							<td valign="top" width="2%">
								<label class="container-chk">
								<input type=checkbox name=auids[]  value='<?php echo $r+1;?>_<?php echo $rowEmpName[2];?>'>
								<span class="checkmark"></span>
								</label>
							</td>
							<td valign="top" nowrap width='10%'>&nbsp;<font class="hfontstyle"><?php echo $rowEmpName[1]; ?> </font>
							<input type="hidden" name=empusername id="empusername_<?php echo $r; ?>" value="<?php echo $elements[$r][0]; ?>" >
							<input type=hidden name=qbid value="<?php echo $elements[$r][9];?>"><!--for quick books data-->
							<br /><font class='taskLabel addtaskBtn' onclick="javascript:AddTaskDetails(this.id)" id="addtaskdetails_<?php echo $r;?>"><i class="fa fa-tasks fa-lg"></i> Add Task Details</font>
						  </td>							
							<?php
							$assignOptions="";
							$uom_query ='';
							$dynamicUstatus = " AND hrcon_jobs.jtype!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y')) <= '".$checking_to."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$checking_from."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$checking_from."'),1)))";
                             
			     				$zque = "select hrcon_jobs.sno, client, project, jtype, pusername, jotype, classid, date_format(str_to_date(s_date,'%m-%d-%Y'),'%m/%d/%Y'), date_format(str_to_date(e_date,'%m-%d-%Y'),'%m/%d/%Y'), assign_no,multi_rates.mulrates from hrcon_jobs left join (select asgnid,GROUP_CONCAT( CONCAT_WS( '^^', ratemasterid, period ) SEPARATOR '&&' ) as mulrates from `multiplerates_assignment` where ratetype = 'billrate' and asgn_mode = 'hrcon' and STATUS = 'Active' GROUP BY asgnid) multi_rates ON multi_rates.asgnid=hrcon_jobs.sno  where $condCk_comp username = '".$empfetchdata."'  and pusername!=''".$dynamicUstatus." $showEmplyoees order by udate DESC";
							$zres=mysql_query($zque,$db);
							$zrowCount = mysql_num_rows($zres); //for billable checkbox
							$multicss ='';
							if($zrowCount > 1)
							{
								$multicss = "style='background:url(/PSOS/images/arrow-multiple-12-red.png); background-repeat:no-repeat;background-position:left top; padding-left: 15px;'word-break:break-all;overflow-wrap: break-word;";
							//$rangRow .= '<span class=afontstylee><img src="/PSOS/images/arrow-multiple-16.png" width="10px" height="10px" title="Multiple Assignments"></span>&nbsp;';
							}else{
								$multicss = "style='word-break:break-all;overflow-wrap: break-word; padding-left;  padding-left: 15px;'";
							}
							?>
							<td valign="top" width='19%' <?php echo $multicss; ?> class="textwrampnewSafari">						
							<?php
							$flg = '';
							$classid = "0";
							$count = 0;
							$classVal = 0;
							$asgnArr = array();
							while($zrow=mysql_fetch_row($zres))
							{
								if($zrow[7] == '00/00/0000' || $zrow[7] == '00/00/2000' || $zrow[7] == NULL || $zrow[7] == '')
									$asgnStartDate = "No Start Date";		
								else
									$asgnStartDate = $zrow[7];
														
								if($zrow[7] == '00/00/0000' || $zrow[8] == '00/00/2000' || $zrow[8] == NULL || $zrow[8] == '')
									$asgnEndDate = "No End Date";
								else
									$asgnEndDate = $zrow[8];
									
								if($asgnStartDate == "No Start Date" && $asgnEndDate == "No End Date")
									$startEnddate = "";
								else
									$startEnddate = "(".$asgnStartDate." - ".$asgnEndDate.")";
								
								if($count == 0)
									$classVal = $zrow[6];
								if($zrow[1] != '0')
								{
									$que = "SELECT cname, ".getEntityDispName('sno', 'cname', 1)." from staffacc_cinfo where sno=".$zrow[1];
									$res=mysql_query($que,$db);
									$row=mysql_fetch_row($res);
									$companyname1=$row[1];
								}
								else
									$companyname1=$companyname;
								
								if($zrow[3]=="AS")
								{
									$flg = sel("AS",$elements[$r][1]);
									$assignOptions.="<option ".sel("AS",$elements[$r][1])." id='".$zrow[0]."-".$zrow[1]."' value='AS' title='".$companyname1." (Administrative Staff)'>".html_tls_specialchars($companyname1,ENT_QUOTES)." (Administrative Staff)</option>";
								}
								else if($zrow[3]=="OB")
								{
									$flg = sel("OB",$elements[$r][1]);
									$assignOptions.="<option ".sel("OB",$elements[$r][1])." id='".$zrow[0]."-".$zrow[1]."' value='OB' title='".$companyname1." (On Bench)'>".html_tls_specialchars($companyname1,ENT_QUOTES)." (On Bench)</option>";
								}
								else if($zrow[3]=="OV")
								{
									$flg = sel("OV",$elements[$r][1]);
									$assignOptions.="<option ".sel("OV",$elements[$r][1])." id='".$zrow[0]."-".$zrow[1]."' value='OV' title='".$companyname1." (On Vacation)'>".html_tls_specialchars($companyname1,ENT_QUOTES)." (On Vacation)</option>";
								}
								else
								{
									$lque="select cname, ".getEntityDispName('sno', 'cname', 1)." from staffacc_cinfo where sno=".$zrow[1];
									$lres=mysql_query($lque,$db);
									$lrow=mysql_fetch_row($lres);
									$clname=$lrow[1];

									if($zrow[4]=="")
									{
										$zrow[4]=" N/A ";
									}

									$flg = sel($zrow[4],$elements[$r][1]);
									// added code here
									if($compfetchdata == "")
									$select1 =sel($zrow[4],$elements[$r][1]);
									 else
									$select1 =sel($zrow[1],$compfetchdata);

									if (!empty($selasgnmtdata)) {

										$select1	= sel($zrow[9], $selasgnmtdata);
									}

									if (isset($asgmt_id) && !empty($asgmt_id)) {

										$select1	= sel($zrow[9], $asgmt_id);
									}
									if($clname != '' && $zrow[2] != '')
									{
										$assignOptions.="<option ".$select1." id='".$zrow[0]."-".$zrow[1]."' class='".$zrow[10]."' title='(".$zrow[4].") ".$startEnddate."&nbsp;&nbsp;".html_tls_specialchars($clname,ENT_QUOTES)." - ".html_tls_specialchars($zrow[2],ENT_QUOTES)."' value='".$zrow[4]."'>".$clname."&nbsp;&nbsp;(".$zrow[4].")&nbsp;-&nbsp;".$zrow[2].$startEnddate."</option>";									
									}
									else if($clname != '' && $zrow[2] == '')
									{
										$assignOptions.="<option ".$select1." id='".$zrow[0]."-".$zrow[1]."' class='".$zrow[10]."' title='(".$zrow[4].") ".$startEnddate."&nbsp;&nbsp;".html_tls_specialchars($clname,ENT_QUOTES)."' value='".$zrow[4]."'>".$clname."&nbsp;&nbsp;(".$zrow[4].")&nbsp;-&nbsp;".$startEnddate."</option>";											
									}
									else if($clname == '' && $zrow[2] != '')
									{
										$assignOptions.="<option ".$select1." id='".$zrow[0]."-".$zrow[1]."' class='".$zrow[10]."' title='(".$zrow[4].") ".$startEnddate."&nbsp;&nbsp;".html_tls_specialchars($zrow[2],ENT_QUOTES)."' value='".$zrow[4]."'>(".$zrow[4].")&nbsp;&nbsp;".$zrow[2].$startEnddate."</option>";										
									}
									else if($clname == '' && $zrow[2] == '')
									{
										$assignOptions.="<option ".$select1." id='".$zrow[0]."-".$zrow[1]."' class='".$zrow[10]."' title='(".$zrow[4].") ".$startEnddate."' value='".$zrow[4]."'>(".$zrow[4].")&nbsp;-&nbsp;".$startEnddate."</option>";												
									}
								}
								$count++;
								if(trim($flg) != '')
									$classid = $zrow[6];
									
								$asgnArr[] = $zrow[0];
							}
							if($elements[$r][8] !="")
								$classid = $elements[$r][8];
							elseif(trim($flg) == '')
								$classid = $classVal;
							
							$que="SELECT eartype FROM hrcon_benifit WHERE username='".$empfetchdata."' AND ustatus='active'";
							$res=mysql_query($que,$db);
							while($data=mysql_fetch_row($res))
							{
								if(strpos($elements[$r][1],"(earn)") === false)
								{
									$checm = "(earn)".$elements[$r][1];
								}
								else
								{
									$checm = $elements[$r][1];
								}
								$assignOptions.="<option ".sel("(earn)$data[0]",$checm)." id='(earn)$data[0]' value='(earn)$data[0]' title='".html_tls_specialchars($data[0],ENT_QUOTES)."'>$data[0]</option>";
							}
							if($zrowCount > 1)
							{
							    /* echo $multi = '<span class=afontstylee><img src="/PSOS/images/arrow-multiple-16.png" width="12px" height="12px" title="Multiple Assignments"></span>&nbsp;'; */
							}
							else
							{
							    echo $multi = '';
							}
							if($elements[$r][1] == '')
							{
								$ele = explode("_", $elements[$r][0]);
								$eleasgnidsplit = $ele[2];
								$elefrom = 'add';
							}
							else
							{
								$eleasgnidsplit = $elements[$r][1];
								$elefrom = 'search';
							}
							$new_asgnid = ($eleasgnidsplit =='') ? $asgnArr[0] : $timesheetObj->getAssignId($eleasgnidsplit);
							$tsexistdata = $timesheetObj->getSubmitedTsDetails($rowEmpName[2],$timesheetObj->getAssignId($eleasgnidsplit), $checking_from, $checking_to);
							if($tsexistdata != '' && $eleasgnidsplit != '')
							{
								$disableFlag = 'disabled ';
							}
							else
							{
								$disableFlag = '';	
							}
							?>
							<select <?php echo $disableFlag;?> id="daily_assignment_<?php echo $rowid;?>"  name=client class="drpdwnacc multitimesheets akkenAssgnSelect" style="padding:0px;" onChange=javascript:getMultipleRate('<?php echo $rowid;?>')>
							<?php	echo $assignOptions; ?>
							</select>
							<label class='afontstylee textwrampnew' id="textlabel_<?php echo $r;?>"  onclick="javascript:AddTaskDetails(this.id)"  style="display:inline;padding: 2px;float:left"><?php if($elements[$r][2] != '') echo html_tls_specialchars(stripslashes($elements[$r][2]),ENT_QUOTES); ?></label>				         		
							
							<input style="display:none;padding:3px; float:left" type='text' name='task' size='50'  class="addtaskdetails" value="<?php echo html_tls_specialchars(stripslashes($elements[$r][2]),ENT_QUOTES);?>" tabindex='<?php echo $tab_index_task;?>' id="multipletask_<?=$r?>">
						  </td>
						  <?php
							 ?>
							<td align=center valign="top"  width='8%'><div <?php echo $divStyle;?>>
							<?php echo clsSelBoxRtn($aryClasses, "class_type", $classid, "drpdwnacc akkenClassSelect", "style='' $disableFlag");?></div>
							</td>
							
							<?php
							//echo "<pre>";
							//print_r($asgnArr);
							//echo "</pre>";
							echo $timesheetObj->getRateTypesWithPayNBill($new_asgnid, $tsexistdata['rate'], $rowid, '', 'create', $elements[$r], 'multiple', $disableFlag); 
							$rateArr = explode(",", $elements[$r][5]);
							if(count($rateArr) > 0 && $disableFlag == "")
							{
								$rowCount = array_sum($rateArr);
							}
							else
							{
								$rowCount = '0.00';	
							}
							?>
						    <td valign="top" class="afontstylee" width='3%'><input type="hidden" name="grandTotal" id="grandTotal_<?php echo $rowid;?>" value="<?php echo $rowCount;?>" ><div id="grandTotalDiv_<?php echo $rowid;?>" ><?php echo number_format($rowCount, 2, '.', '');?></div><input type="hidden" name="hours" id="daytotalhrs_<?php echo $rowid;?>" value="<?php echo $rowCount;?>" ><div id="daytotalhrsDiv_<?php echo $rowid;?>" style="display:none;"><?php echo number_format($rowCount, 2, '.', '');?></div><input type="hidden" name="days" id="totaluomdays_<?php echo $rowid;?>" value="<?php echo $rowCount;?>" ><div id="daystotalDiv_<?php echo $rowid;?>" style="display:none;"><?php echo number_format($rowCount, 2, '.', '');?></div><input type="hidden" name="miles" id="totaluommiles_<?php echo $rowid;?>" value="<?php echo $rowCount;?>" ><div id="milestotalDiv_<?php echo $rowid;?>" style="display:none;"><?php echo number_format($rowCount, 2, '.', '');?></div><input type="hidden" name="units" id="totaluomunits_<?php echo $rowid;?>" value="<?php echo $rowCount;?>" ><div id="unitstotalDiv_<?php echo $rowid;?>" style="display:none;"><?php echo number_format($rowCount, 2, '.', '');?></div></td>
					     </tr>
						<!--</table>-->
						<input type=hidden name=jobtype value="<?php echo $elements[$r][7]; ?>">					
						<input type=hidden name=sdates value="<?php echo $elements[$r][9]; ?>">
						<input type=hidden name=status value="<?php echo $elements[$r][10]; ?>">
						<input type=hidden name=auser value="<?php echo $elements[$r][11]; ?>">										
						<input type=hidden name=sno_ts value="<?php echo $elements[$r][12]; ?>">
						<input type=hidden name=edates value="<?php echo $elements[$r][13]; ?>">
						
						<!--Dummy hidden-->
						<input type=hidden name=hourstype value="">
						<input type=hidden name=billable value="">
						<!--Dummy hidden-->
						<?php
						 if($elements[$r][4] == "")
							//echo "<script>getBillT($r);</script>";

						echo "<script>change_font($r);</script>";						
				}
			?>
			<!--</td></tr>-->
			<!-- <tr>						
				<td colspan =<?=$colspan?>>&nbsp;</td>						
			</tr> -->
			</table>		
		
		</div>
		</td>
		</tr>
		<?
		}
		else
		{
		?>
		   <table width='100%'>
			<tr>
				<td align="center" height="50" valign="middle" align="middle"><font class=afontstyle align="center">Select an employee to submit Timesheet</font></td>
			</tr>
			</table>
		<?php
		}
		?>
        
		<tr>
        
		<div id="botheader" class="NewGridBotBg" style="width:99%; margin:0px auto">
		
		
		<?php	   
		$heading="time.gif~Create&nbsp;Timesheet";
		//$menu->showHeadingStrip1($name,$link,$heading);
		?>

		</div>
	</tr>
	</table>
	</td>
	</div>	
	
	</table>
	<div align="center" class="divpopup" id="SaveAlert" style="height:200px; width:710px; border:0px thick #000000; display:none;">
		<table style="width:100%; height:95%; background-color:#FFFFFF;" border="0">
			<tr valign="middle">
				<td width="99%" style="text-align:center;">
				<font style='font-family:Arial, Helvetica, sans-serif; size=12px'; >Processing, Please wait...</font><br /><br /><img src='/BSOS/images/loading_icon_small.gif' align=middle />
				</td>
			<tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert('-1');" class="time-alert-button" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr>
			</tr>
		</table>
	</div>	
	<div id=preloader>
		<table width=100% height=100%>
			<tr>
				<td valign=middle align=center><font style='font-family:Arial, Helvetica, sans-serif; size=12px'; >Processing, please wait...</font><br /><br />
				<img src='/BSOS/images/loading_icon_small.gif' align=middle />
				</td>
			</tr>
		</table>
	</div>
</form>
<script language="javascript" type="text/javascript">
$(document).ready(function(){
	//$("#empnames").select2();
	$(".drpdwnacc").select2({minimumResultsForSearch: -1});
	$("#empnames").select2({
    
	        placeholder: "Select an Employee",
	        minimumInputLength: 0,
	        closeOnSelect: true,
	        //data:dropdownData(),
	        ajax: {
	            type: "POST",
	            url: "/include/timesheet/getSelectorData.php",
	            dataType: 'json',
	            quietMillis: 250,
	            delay: 250,
	            data: function (params) {
	            	var customersids = $('#empnames').val();
					var queryParameters = {
					  q: params.term,
					  page: params.page,
					  getServicedatefrom :'<?php echo $checking_from;?>',  
					  getServicedateto :'<?php echo $checking_to;?>',
					  getMultiEmployeeSearchVal: params
					}
					return queryParameters;
				},
				initSelection: function(element, callback) {
					//alert(element);
				    callback({ id: element.val(), text: element.attr('data-init-text') });
				},
				results: function (data, params) {
				    params.page = params.page || 1;
				    return {
				        results: data.results,
				        pagination: {
				            more: (params.page * 10) < data.count_filtered
				        }
				    };
				},
				cache: true
			},
	            
	        language: {
		       	noResults: function(){
		           return "No Employee Found";
		       	},
		       	/*searching: function(){
			        return "<span><i class='fa fa-spin fa-spinner'></i>Searching Please Wait</span>"
			    }*/
		   	},
	        escapeMarkup: function (m) {
	        	return m; 
	        }
	    });

});	
var colratecount = document.getElementById("ratecount").value;
function chainNavigation() {	

	//Arrow UP/Down Code
	for(var i=0; i<colratecount; i++){
		var $box_Regular = $(':input.timesheetRate'+i+':enabled');
		$box_Regular.each(function(i) {
			$(this).data('next', $box_Regular[i + 1]);
			$(this).data('prev', $box_Regular[i - 1]);
		});
	}	
}
//Function for getting the titles for UOM timesheet
function gettitles(title){
	if(title == 'Day' || title == 'Miles' || title == 'Units'){
		return 0;
	}else{
		return 1;
	}
}
function TimesheetCalcMuti(keyrow,row,total,rowfocus)
{
		var dtot = 0.00;
		var tempTotaldays= 0.00;
		var tempTotalmiles= 0.00;
		var tempTotalunits= 0.00;
		var grandTotal= 0.00;
		for (var i=0;i<total;i++)
		{ 
			var eachDayrowVal = $("#MainTable input[id=daily_rate_"+i+"_"+row+"]").val();	
			if(eachDayrowVal!=''){
				var hrs_id = "daily_rate_"+i+"_"+row;
				var frm1 = document.getElementById(hrs_id);
				var getAttr = frm1.getAttribute('rate_uom');
				var gettitle = gettitles(getAttr);
				//Getting the UOM timesheet totals
				if(isHoursNew(frm1,getAttr))
				{
					if(gettitle == 1){
				  		dtot =  parseFloat(dtot) + parseFloat(eachDayrowVal);				  				 
					}else if(gettitle == 0){
						if(getAttr == 'Day'){
							tempTotaldays = parseFloat(tempTotaldays) + parseFloat(eachDayrowVal) ;	
						}else if(getAttr == 'Miles'){
							tempTotalmiles = parseFloat(tempTotalmiles) + parseFloat(eachDayrowVal) ;	
						}else if(getAttr == 'Units'){
							tempTotalunits = parseFloat(tempTotalunits) + parseFloat(eachDayrowVal) ;	
						}
					}
					grandTotal = parseFloat(grandTotal) + parseFloat(eachDayrowVal) ;					  				 
				}else
				{
				  frm1.value='';				 
				  return false;
				}
			}
		}
		$("#daytotalhrs_"+row).val(NumberFormatted(dtot));
		$("#daytotalhrsDiv_"+row).html(NumberFormatted(dtot));
		//UOM timesheet for displaying the totals for units/miles/days
		$("#totaluomdays_"+row).val(NumberFormatted(tempTotaldays));
		$("#daystotalDiv_"+row).html(NumberFormatted(tempTotaldays));
		
		$("#totaluommiles_"+row).val(NumberFormatted(tempTotalmiles));
		$("#milestotalDiv_"+row).html(NumberFormatted(tempTotalmiles));
		
		$("#totaluomunits_"+row).val(NumberFormatted(tempTotalunits));
		$("#unitstotalDiv_"+row).html(NumberFormatted(tempTotalunits));
		
		$("#grandTotal_"+row).val(NumberFormatted(grandTotal));
		$("#grandTotalDiv_"+row).html(NumberFormatted(grandTotal));
		
}
// Arrow UP/Down Main Function
	   for(var i=0; i<colratecount; i++){	
		$(':input.timesheetRate'+i+':enabled').bind('focus', function() {
			$(this).select();
				}).bind('keydown', function(e) {    
					if (e.which === 40) {
						var $next = $(this).data('next');
						if ($next != null) {
							$next.select();
						}
					} else if (e.which === 38) {
						var $prev = $(this).data('prev');
						if ($prev != null) {
							$prev.select();
						}
					}
		});
	}
function AddTaskDetails(rownumberval)
{
	var rownumberarray= rownumberval.split("_");
	var rownumber = rownumberarray[1];
	$("#multipletask_"+rownumber).show();
	$("#multipletask_"+rownumber).focus();
	$("#textlabel_"+rownumber).hide();
}
$('.addtaskdetails').blur(function() {  
	     var id = $(this).attr('id');
		 id = id.replace("multipletask_", "");		
         if ($.trim(this.value) == ''){  
			 this.value = (this.defaultValue ? this.defaultValue : '');  
		 }
		 else{			
			 $("#textlabel_"+id).html(this.value);
		 }
		 
		 $(this).hide();
		 $("#textlabel_"+id).show();		
     });
	  
	  $('.addtaskdetails').keypress(function(event) {
		  if (event.keyCode == '13') {
			var id = $(this).attr('id');
			id = id.replace("multipletask_", "");			 
			 if ($.trim(this.value) == ''){  
				 this.value = (this.defaultValue ? this.defaultValue : '');  
			 }
			 else
			 {				 
				 $("#textlabel_"+id).html(this.value);
			 }
			 
			 $(this).hide();
			 $("#textlabel_"+id).show();			 
		  }
	  });
chainNavigation();

function doEditAssign(hhid, eeid, appno, rowid)
{
	var v_heigth = 700;
	var v_width  = 950;
	var top=(window.screen.availHeight-v_heigth)/2;
	var left=(window.screen.availWidth-v_width)/2;	
	remote=window.open("redirectassignment.php?source=timesheet&assign=edit&appno="+appno+"&hhid="+hhid+"&eeid="+eeid+"&rowid="+rowid,"","width="+v_width+"px,height="+v_heigth+"px,statusbar=no,menubar=no,scrollbars=yes,left=30,top=30,dependent=yes");
	remote.focus();
}

$('a').click(function(){		
	var me = $(this);
	me.attr("id", "timesubmit");
});
document.getElementById("main").style.display = "";
document.getElementById("preloader").style.display = "none";

// Preloader function
function showPreloader(act){	
	document.getElementById("preloader").style.display = "";
	document.getElementById("main").style.display = "none";
	setTimeout(function() {
		document.getElementById("main").style.display = "";
		validateMulti(act);
		document.getElementById("preloader").style.display = "none";
	    }, 0);
}

//Function for rate types placeholders
function rate_type_con(asgnrate_type){
	switch(asgnrate_type){
		case 'UOM_DAY':
			asgnratetype = 'Day';
			break;
		case 'UOM_MILE':
			asgnratetype = 'Miles';
			break;
		case 'UOM_UNIT':
			asgnratetype = 'Units';
			break;
		default:
			asgnratetype = 'Hours';
	}
	return asgnratetype;
}
//UOM multiple timesheet placeholders
function get_cur_placeholders(id_val,cindex){
	$("#"+id_val).closest("tr").find("input.rates").attr("placeholder", "");
	$("#"+id_val).closest("tr").find("input.rates").attr("title", "");
	$("#"+id_val).closest("tr").find("input.rates").attr("rate_uom", "");

	if(typeof $("#"+id_val+" option:selected").attr('class') != 'undefined' && $("#"+id_val+" option:selected").attr('class') != 'false'){
		var asgn_rates = $("#"+id_val+" option:selected").attr('class').split("&&");

		for(var p = 0; p < asgn_rates.length; p++){
			asgnrate=asgn_rates[p].split("^^");
			var asgnratetype = rate_type_con(asgnrate[1]);
			if($("#"+id_val).closest("tr").find("input."+asgnrate[0]).attr('disabled') !='disabled'){
				$("#"+id_val).closest("tr").find("input."+asgnrate[0]).attr("placeholder", asgnratetype);
			}	
			$("#"+id_val).closest("tr").find("input."+asgnrate[0]).attr("title", asgnratetype);
			$("#"+id_val).closest("tr").find("input."+asgnrate[0]).attr("rate_uom", asgnratetype);
			
		}

	}else{
		
		$("#"+id_val).closest("tr").find("input.rate1").attr("placeholder", "Hours");$("#"+id_val).closest("tr").find("input.rate2").attr("placeholder", "Hours");$("#"+id_val).closest("tr").find("input.rate3").attr("placeholder", "Hours");
		$("#"+id_val).closest("tr").find("input.rate1").attr("title", "Hours");$("#"+id_val).closest("tr").find("input.rate2").attr("title", "Hours");$("#"+id_val).closest("tr").find("input.rate3").attr("title", "Hours");
		$("#"+id_val).closest("tr").find("input.rate1").attr("rate_uom", "Hours");$("#"+id_val).closest("tr").find("input.rate2").attr("rate_uom", "Hours");$("#"+id_val).closest("tr").find("input.rate3").attr("rate_uom", "Hours");
		
	}
			
			
}
//Function for Saving timesheet separately Regular and UOM timesheet
function setRatesData(){
	$('#ratesdata').val('');
	var v=1;var ratesdata = '';
	$('.multitimesheets').each(function(){
		if(typeof $("#daily_assignment_"+v+" option:selected").attr('class') != 'undefined' && $("#daily_assignment_"+v+" option:selected").attr('class') != 'false'){
			var rates = $("#daily_assignment_"+v+" option:selected").attr('class');
		}else{
			var rates = "Hours";
		}
		var empinputId = parseFloat(v)-parseFloat(1)
		var empusername = $('#empusername_'+empinputId).val();
		if(ratesdata == ''){
			ratesdata = empusername+"||"+rates; 
		}
		else{
			ratesdata += "|^AKKEN^|"+empusername+"||"+rates;
		}
		$('#ratesdata').val(ratesdata);
		v++;
	});
}
var u=1;

$('.multitimesheets').each(function(){
	get_cur_placeholders("daily_assignment_"+u,u);
	TimesheetCalcMuti("",u,colratecount,"");
	u++;
});
setRatesData();

</script>