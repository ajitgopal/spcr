<?php

/*      
By	         : Priyanka Varanasi
Modified Date: June 24,2016
Purpose		 : [#807524] Time sheet Issue -If CSS user delete his time sheet ,then the same time timesheet associated with another user also get deleted.
 */
	require_once('global.inc');
	require_once($akken_psos_include_path.'commonfuns.inc');
	require_once('timesheet/class.timeintimeout.php');
	require_once('Menu.inc');
	require_once('json_functions.inc');
	global $db;

	$date	= date('Y-m-d');
	$efrom1	= getFromEmailID($username);

	$objEmpMenu		= new EmpMenu();
	$objTimeInTimeOut	= new TimeInTimeOut($db);

	// GETTING MAX REGULAR HOURS SPECIFIED @ PAYROLL SETUP
	$max_regular_hours	= $objTimeInTimeOut->getMaxRegularHours();

	// GETTING MAX OVERTIME TIME HOURS SPECIFIED @ PAYROLL SETUP
	$max_overtime_hours	= $objTimeInTimeOut->getMaxOverTimeHours();

	// GETTING TIME INCREMENT SPECIFIED @ PAYROLL SETUP
	$time_increment	= $objTimeInTimeOut->getTimeIncrement();
	
	// GETTING SEVENTH DAY RULE DETAILS SPECIFIED @ PAYROLL SETUP
	$seventhdayrule_info 		= array();
	$seventhdayrule_info		= $objTimeInTimeOut->getSeventhDayRuleDetails();
	$seventhdayrule_flag 		= $seventhdayrule_info[0];
	$seventhdayrule_maxregular    	= $seventhdayrule_info[1];
	$seventhdayrule_maxovertime   	= $seventhdayrule_info[2];
	$seventhdayrule_weekendday   	= $seventhdayrule_info[3];

	// GETTING WEEK RULE DETAILS SPECIFIED @ PAYROLL SETUP
	$weekrule_info	= $objTimeInTimeOut->getWeekRuleDetails();

	$ruletype_flag		= $weekrule_info[0];
	$weekrule_maxregular	= $weekrule_info[1];
	$weekrule_maxovertime	= $weekrule_info[2];
	$weekrule_maxhourspref	= $weekrule_info[3];
	$weekrule_maxhoursaday	= $weekrule_info[4];
	$payroll_weekendday	= $weekrule_info[5];
	$overwrite_weekend_rule = $weekrule_info[6];
		

	$timeSubmitFlag		= 0;
	$rate_types_count	= 0;
	$mod_val = $_GET['module'] == ''?$module:$_GET['module'];
	$edit_mode		= ($_GET['mode'] == true) ? 'edit': 'create';

	$title		= 'Create&nbsp;Timesheet';
	$action_url	= '/include/savetime.php';

	if (isset($ts_status) & !empty($ts_status)) {

		$ts_status	= $objTimeInTimeOut->getTimesheetStatusName($ts_status);
	}

	if ($mode == 'edit') {

		$title		= 'Edit&nbsp;Timesheet';
		$action_url	= '/include/edit_savetime.php';

		$sql	= "SELECT 
					MIN(t1.sdate) servicedate, MAX(t1.edate) servicedateto, t2.username, t1.issues, t1.notes, t1.overwrite_weekend_rule_status
					FROM 
						par_timesheet t1 
						INNER JOIN timesheet_hours t2 ON t1.sno = t2.parid 
					WHERE 
						t1.sno = '".$sno."' 
					GROUP BY 
						t1.sdate";
		$result	= mysql_query($sql, $db);
		$row	= mysql_fetch_row($result);

		$time_fromdate_dbVal	= date('m/d/Y', strtotime($row[0]));
		$time_todate_dbVal	= date('m/d/Y', strtotime($row[1]));
		$overwrite_weekend_rule_dbVal	= $row[5];

		if (!empty($servicedate)) {

			$servicedate	= $servicedate;

		} else {

			$servicedate	= date('m/d/Y', strtotime($row[0]));
		}

		if (!empty($servicedateto)) {

			$servicedateto	= $servicedateto;

		} else {

			$servicedateto	= date('m/d/Y', strtotime($row[1]));
		}

		$empnames	= $row[2];
		$issues		= $row[3];
		$notes		= $row[4];
	}

	if ($time_fromdate_dbVal != $servicedate || $time_todate_dbVal != $servicedateto) {

		$timeSubmitFlag = 1;
	}

	if (isset($servicedate) && isset($servicedateto)) {

		$timesheet_date_arr	= $objTimeInTimeOut->GetDays($servicedate, $servicedateto);

	} else {

		$timesheet_date_arr	= $objTimeInTimeOut->getWeekdays($date);
		$timesheet_start_date	= explode(" ", $timesheet_date_arr[0]);
		$timesheet_end_date	= explode(" ", $timesheet_date_arr[6]);
		$servicedate		= $timesheet_start_date[0];
		$servicedateto		= $timesheet_end_date[0];
	}

	if ($ruletype_flag == 'weekrule') {

		$wk_ruleflag	= 'Y';

		$start_date	= current(explode(" ", current($timesheet_date_arr)));
		$week_days	= $objTimeInTimeOut->getWeekDaysFromStartDate($start_date);
		if($overwrite_weekend_rule == "Y")
		{
			//get the previous day and assigning it as payroll weekend day
			$prev_day 		= date('l', strtotime($start_date .' -1 day'));
			$payroll_weekendday 	= $prev_day;
		}

		//Loop for getting the weekend date based on the payroll weekend day
		foreach ($week_days as $key => $val) {

			$wkday	= end(explode(" ", $val));

			if ($wkday == $payroll_weekendday) {

				$wkenddate	= current(explode(" ", $val));
				break;
			}
		}

		//Checking whether weekrule is applicable or not for the selected date ranges
		foreach ($timesheet_date_arr as $key => $val) {

			$wkdate	= current(explode(" ", $val));

			if (strtotime($wkdate) > strtotime($wkenddate)) {

				$wk_ruleflag	= 'N';
				break;
			}
		}
	}

	$employees	= $objTimeInTimeOut->getEmployees($module, $username, $servicedate, $servicedateto);

	
	if ($empnames == '') {

		$empnames	= $objTimeInTimeOut->new_first_user;
	}

	$emp_drop_down	= $objTimeInTimeOut->buildEmpList($employees, $empnames);
	if($module=='Accounting' && $module !='Client'){
		
		$uname = $_POST['empnames'];
		$emp_uname = ($uname== '')?$empnames:$uname;
		$query="select name,sno from emp_list where username='".$emp_uname."'";
		$res=mysql_query($query,$db);
		$myrow=mysql_fetch_row($res);
		$ename = $myrow[1].'-'.$myrow[0];
	}
	$asgnflag	= $objTimeInTimeOut->checkAssignmentExists($empnames, $servicedate, $servicedateto);

	if ($asgnflag) {

		if ($module == 'Accounting') {

			$name	= explode("|","fa-plus-square~Add Row|fa fa-envelope~Submit|fa-ban ~Cancel");
			$link	= explode("|","javascript:void(0)|javascript:validateTimeInOut('submit','".$mode."');|javascript:self.close()");

		} elseif ($module == 'MyProfile') {
			if($ts_status == "rejected"){
				$name	= explode("|","fa-plus-square~Add Row|fa fa-envelope~Submit|fa-ban~Cancel");
				$link	= explode("|","javascript:void(0)|javascript:validateTimeInOut('submit','".$mode."');|javascript:self.close()");
			}
			else{
				$name	= explode("|","fa-plus-square~Add Row|fa fa-floppy-o~Save|fa fa-envelope~Submit|fa-ban~Cancel");
				$link	= explode("|","javascript:void(0)|javascript:validateTimeInOut('save','".$mode."');|javascript:validateTimeInOut('submit','".$mode."');|javascript:self.close()");
			}			

		} elseif ($module == 'Client') {

			$name	= explode("|","fa-plus-square~Add Row|fa fa-envelope~Submit|fa-ban~Cancel");
			$link	= explode("|","javascript:void(0)|javascript:validateTimeInOut('submit','".$mode."');|javascript:self.close()");
		}

	} else {

		$name	= explode("|","fa-ban~Cancel");
		$link	= explode("|","javascript:self.close()");
	}

	/* Fetches Disclaimer Content */
	$dflag	= false;

	// Check user preferences
	// Client
	if ($module == "Client" ){
		$sqlSelfPref="select sno, username, joborders, candidates, assignments, placements, billingmgt, timesheet, invoices, expenses, joborders_owner from selfservice_pref where username='".$username."'";
		$resSelfPref=mysql_query($sqlSelfPref,$db);
		$userSelfServicePref=mysql_fetch_row($resSelfPref);
		if(strpos($userSelfServicePref[7],"+8+") || strpos($userSelfServicePref[7],"+8")){
			$acctype = "CSS";
			$dflag = true;
		}
	}
    
	// MyProfile
	$lockdown_flag = "allow_duplicate";	
	$ess_user = "NO";
	if($module == "MyProfile" )
	{
		// Check user preferences
		$userpref_que = "select myprofile from sysuser where username='".$username."'";
		$userpref_res = mysql_query($userpref_que,$db);
		$userSelfServicePref = mysql_fetch_row($userpref_res);
		if(strpos($userSelfServicePref[0],"+20+") || strpos($userSelfServicePref[0],"+20")){
			$acctype = "SSU";
			$dflag = true;
		}
		
		if(chkLoginUserIsEss($username))
		{
			$ess_user	= "YES";
			$sel_pref_query	= "SELECT ess_lockdown FROM cpaysetup WHERE status='ACTIVE'";
			$res_pref_query	= mysql_query($sel_pref_query, $db);
	
			if (mysql_num_rows($res_pref_query) > 0) {
	
				$row_pref_query = mysql_fetch_object($res_pref_query);
				$ess_lockdown_pref = $row_pref_query->ess_lockdown;

				if(strpos($ess_lockdown_pref,'2')=== false) //if not availabe in the preferences
				{
					$lockdown_flag = "dont_allow_duplicate";
				}
			}
		}
	}
	
	$disclaimer_flag = false;
	if ($dflag) {
		$dis_selqry = "select sno,dis_message from css_ess_disclaimer where username = '".$username."' and acctype='".$acctype."'";
		$dis_resqry = mysql_query($dis_selqry,$db);
		$dis_numrows = mysql_num_rows($dis_resqry);
		if($dis_numrows > 0)
		{
			$dis_rowqry = mysql_fetch_row($dis_resqry);
			$dis_sno = $dis_rowqry[0];        
			$dis_message = htmlspecialchars_decode($dis_rowqry[1]);
			$disclaimer_flag = true;
		}
	}
	if($ts_status == "approved") {
			
		$status_cond = "and status NOT IN('Backup','Approved')";
	} elseif($ts_status == "ER"){
		
		$status_cond = "and status NOT IN('Backup','ER')";
	}elseif($ts_status == "saved"){
		
		$status_cond = "and status NOT IN('Backup','Saved')";
	}
	
	$rowid_list_not_in_current_status_qry = "select DISTINCT(th.rowid) from timesheet_hours as th INNER JOIN par_timesheet as pt on(pt.sno = th.parid) where th.parid = '".$sno."' and status NOT IN('Deleted','Backup') and pt.sdate >= '".$servicedate."' and pt.edate >= '".$servicedateto."'  AND hourstype IN('rate1','rate2','rate3') order by th.rowid";
		
	$rowids_result	= mysql_query($rowid_list_not_in_current_status_qry, $db);
	$rowids_count = mysql_num_rows($rowids_result);
	$rowid_list = array();
				
	if($rowids_count > 0)
	{			
		while($result = mysql_fetch_array($rowids_result)) {
			$rowid_list[] = $result['rowid']; 
		
		}
		$rowids_list_not_in_current_status = implode(',',$rowid_list);
		
		
	} else {
		
		$rowids_list_not_in_current_status	= "";
	}
						
			
	$rowid_hours_list = array();
			
	if(count($rowid_list)>0){
		
		foreach($rowid_list as $rowidvalue){
		
			 $total_hours_for_eachrowid_qry = "select IFNULL(sum(th.hours),'0.00') as rowhours,th.rowid from timesheet_hours as th INNER JOIN par_timesheet as pt on(pt.sno = th.parid) where th.parid = '".$sno."' and status NOT IN('Deleted','Backup') and pt.sdate >= '".$servicedate."' and pt.edate >= '".$servicedateto."'  AND hourstype IN('rate1','rate2','rate3') and th.rowid = '".$rowidvalue."' order by th.rowid";
			
			$total_hours_for_eachrowid_result	= mysql_query($total_hours_for_eachrowid_qry, $db);
			$total_hours_for_eachrowid_count 	= mysql_num_rows($total_hours_for_eachrowid_result);
			$hours_result 				= mysql_fetch_assoc($total_hours_for_eachrowid_result);
			
			if($total_hours_for_eachrowid_count > 0)
			{	
				$rowid_hours_list[$hours_result['rowid']] = $hours_result['rowhours'];	
				
			}
		}
		//echo "<pre>";print_r($rowid_hours_list);
	
		//echo $rowid_hours_for_each_list = implode(',',$rowid_hours_list);
		//echo $rowid_hours_before_submit = $rowid_hours_list;
	}
			
	$prev_total_hours_qry = "select IFNULL(sum(th.hours),'0.00') as prev_total_hrs from timesheet_hours as th INNER JOIN par_timesheet as pt on(pt.sno = th.parid) where th.parid = '".$sno."' and status NOT IN('Deleted','Backup') and pt.sdate >= '".$servicedate."' and pt.edate >= '".$servicedateto."' AND hourstype IN('rate1','rate2','rate3')";
			
	$prev_total_hours_res	= mysql_query($prev_total_hours_qry, $db);
	$prev_total_hours_count = mysql_num_rows($prev_total_hours_res);
			
	if($prev_total_hours_count > 0)
	{
		$prev_total_hours_arr	= mysql_fetch_assoc($prev_total_hours_res);
		$prev_total_hours 	= $prev_total_hours_arr['prev_total_hrs'];
		
	} else {
		
		$prev_total_hours	= 0.00;
	}	

	$tito_ovthours_qry = "select sum(hours),max(th.rowid) from timesheet_hours as th INNER JOIN par_timesheet as pt on(pt.sno = th.parid) where th.parid = '".$sno."'  ".$status_cond." and pt.sdate >= '".$servicedate."' and pt.edate >= '".$servicedateto."'  AND hourstype IN('rate2')";

	$tito_ovthours_res	= mysql_query($tito_ovthours_qry, $db);
	$tito_ovthours_count 	= mysql_num_rows($tito_ovthours_res);
	if($tito_ovthours_count > 0)
	{
		$tito_ovthours_arr	= mysql_fetch_row($tito_ovthours_res);
		$tito_ovthours 		= $tito_ovthours_arr[0];
		$tito_ovthours_rowid 	= $tito_ovthours_arr[1];
	} else {
		
		$tito_ovthours	= "";
	}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" >
		<title>TimeInTimeOut</title>
		<link href="/BSOS/css/fontawesome.css" rel="stylesheet" type="text/css">
		<link href="/BSOS/Home/style_screen.css" rel="stylesheet" type="text/css">
		<link href="/BSOS/css/educeit.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="/BSOS/css/calendar.css" media="screen" type="text/css">		
		<link rel="stylesheet" href="/BSOS/css/timeintimeout.css" media="screen" type="text/css">
		<link rel="stylesheet" href="/BSOS/css/jquery.timepicker.css" media="screen" type="text/css">
		<link rel="stylesheet" href="/BSOS/css/base.css" media="screen" type="text/css">
			
		<link rel="stylesheet" href="/BSOS/popupmessages/css/popup_message.css" media="screen" type="text/css">
		<link rel="stylesheet" href="/BSOS/css/popup_styles.css" media="screen" type="text/css">
		
		<link rel="stylesheet" href="/include/timesheet/css/timesheet.css" media="screen" type="text/css">
		<link rel="stylesheet" href="/BSOS/css/merge.css" media="screen" type="text/css">
		<link type="text/css" rel="stylesheet" href="/BSOS/css/timeSheetselect2.css">
       

		<script type="text/javascript">
			// DECLARING GLOBAL VARIABLES - USED IN timeintimeout.js
			module	= "<?php echo $module;?>";
			mode	= "<?php echo $mode;?>";

			ruletype_flag	= "<?php echo $ruletype_flag;?>";
			time_increment	= "<?php echo $time_increment;?>";

			max_reg_hrs	= "<?php echo $max_regular_hours;?>";
			max_ovt_hrs	= "<?php echo $max_overtime_hours;?>";

			if (ruletype_flag == "dayrule") {

				// SEVENTH DAY RULE SPECIFIC
				seventhdayrule_flag		= "<?php echo $seventhdayrule_flag;?>";
				seventhdayrule_weekmaxregular	= "<?php echo $seventhdayrule_maxregular;?>";
				seventhdayrule_weekmaxovertime	= "<?php echo $seventhdayrule_maxovertime;?>";
				seventhdayrule_weekendday	= "<?php echo $seventhdayrule_weekendday;?>";

				// WEEK RULE SPECIFIC
				wk_maxlimithoursaday	= 0;
				wk_maxlimithourspref	= "N";
				wk_payroll_weekendday	= "";

			} else if (ruletype_flag == "weekrule") {

				wk_max_reg_hrs	= "<?php echo $weekrule_maxregular;?>";
				wk_max_ovt_hrs	= "<?php echo $weekrule_maxovertime;?>";

				// WEEK RULE SPECIFIC
				wk_ruleflag		= "<?php echo $wk_ruleflag;?>";
				wk_maxlimithourspref	= "<?php echo $weekrule_maxhourspref;?>";
				wk_maxlimithoursaday	= "<?php echo $weekrule_maxhoursaday;?>";
				wk_payroll_weekendday	= "<?php echo $payroll_weekendday;?>";

				// SEVENTH DAY RULE SPECIFIC
				seventhdayrule_flag		= 0;
				seventhdayrule_weekmaxregular	= 0;
				seventhdayrule_weekmaxovertime	= 0;
				seventhdayrule_weekendday	= "";
				
				rowid_hours_for_each_list = <?php echo json_encode($rowid_hours_list); ?>;
				tito_total_ovthours_basedon_status = "<?php echo $tito_ovthours; ?>";
				tito_ovthours_rowid = "<?php echo $tito_ovthours_rowid; ?>";
			}

		</script>

		<script type="text/javascript" src="/BSOS/scripts/calendar.js"></script>
		<script type="text/javascript" src="/BSOS/scripts/date_format.js"></script>
		<script type="text/javascript" src="/BSOS/scripts/common_ajax.js"></script>
		<script type="text/javascript" src="/BSOS/scripts/jquery-1.8.3.js"></script>
		<script type="text/javascript" src="/BSOS/scripts/validatecommon.js"></script>
		<script type="text/javascript" src="/BSOS/scripts/jquery.inputmask.js"></script>		
		<script type="text/javascript" src="/BSOS/scripts/timeintimeout.js"></script>
		<script type="text/javascript" src="/BSOS/scripts/indexoff_ie.js"></script>
		<script type="text/javascript" src="/BSOS/popupmessages/scripts/popupMsgArray.js"></script>
		<script type="text/javascript" src="/BSOS/popupmessages/scripts/popup-message.js"></script>
		<script type="text/javascript" src="/BSOS/scripts/select2_V4.0.3.js"></script>
		<?php if($mod_val!='Accounting'){?>
		<script type="text/javascript" src="/BSOS/scripts/select2.js"></script> 
		<?php }?>
		<!-- Custom Select Box Styles-->
		<link rel="stylesheet" type="text/css" media="screen" href="/BSOS/css/customSelectNew.css"/>

		<?php if($mod_val =='Accounting'){?>
		<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/select2_V_4.0.3.css">
		<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/gigboardCustom.css">
		<?php }?>
		<script type="text/javascript" src="/BSOS/scripts/customSelectNew.js"></script>
		<style type="text/css">
			.chremove{
				border: none;
			}
			#MainTable tr.tr_clone td, tr.weekly_row td
			{
				padding:0px 2px 10px 2px;
				white-space: nowrap !important;
			}
			.nowrap{background-position:left 14px !important;}
			.CustomGrandTotal .totbg{font-weight: normal !important;}
			.totbg
				{
				    background-color:#f6f7f9;
				    font-family: Arial;
					font-size:14px;
					font-style: normal;
					line-height:24px;
					border-top:solid 1px #ccc;
				}
				.smalltextfont{font-size: 12px;}
				.tr_clone:focus{background-color:#3fb8f1 !important; }
		</style>
		<?php
			if($disclaimer_flag && $module == "Client")
			{
		?>
				<style>
					#DHTMLSuite_modalBox_contentDiv{
						width: 561px !important;
						/*height: 396px !important;*/
						height: 496px !important;
						left: 50% !important;
                                                margin-left: -280px;
					}
					.alert-w-chckbox-chkbox-time-exe-moz{
						height: 390px !important;
					}
					.alert-w-chckbox-chkbox-time-exe-ie{
						height: 363px !important;
					}
					.alert-cntrbtns-time-exe{
						/*margin-left: 185px;*/
						margin: 15px 0 0 185px;
					}
					#DHTMLSuite_modalBox_iframe{
						left: 426px !important;
					}
					.alert-ync-container{
						height: 100% !important;
					}
					#DHTMLSuite_modalBox_shadowDiv{
						left: 410px !important;
					}
					div.alert-w-chckbox-chkbox-content-time-exe table{
						height: 97% !important;
					}
					@media screen and (-webkit-min-device-pixel-ratio:0) {
					
					}
					@-moz-document url-prefix() {
						
					}
				</style>
		<?php
			}
			if($disclaimer_flag && $module == "MyProfile")
			{
		?>
			<style>
				#DHTMLSuite_modalBox_contentDiv{	
					height: 420px !important;
                    left: 50% !important;
                    margin-left: -280px;
				}
				.alert-w-chckbox-chkbox-time-exe-moz{
					height: 277px !important;
				}
				.alert-w-chckbox-chkbox-time-exe-ie{
					height: 310px !important;
				}
				.alert-cntrbtns-time-exe{
					margin-left: 208px; 
				}
				.alert-w-chckbox-chkbox-content-time-exe{
					font-size: 1.0em;
				}
				#disclaimer_container{
					height: 290px !important;
				}
				.alert-ync-title-time-exe{
					font-size: 1.0em !important;
				}
				@media screen and (-webkit-min-device-pixel-ratio:0) {
					#disclaimer_container{
						height: 280px !important;
					}
					.alert-w-chckbox-chkbox-time-exe-moz{
						height: 310px !important;
					}
				}
				@-moz-document url-prefix() {
					.alert-w-chckbox-chkbox-time-exe-moz{
						height: 297px !important;
					}
					#disclaimer_container{
						height: 280px !important;
					}
					.alert-cntrbtns-time-exe{
						margin: 7px 0 0 200px !important; 
					}
				}
			</style>
		<?php
			}
		?>
<style type="text/css">
#dynsndiv{ position:fixed !important;}    
@media only screen and (max-width: 1024px) {
.CustTimeSheetHed{ font-size: 14px !important; }
.SummaryTopBg{ padding-top: 0px;}
.FontSize-16{ font-size: 13px;}
.TMEmpWid{float: left; clear: both;}
.TMEResp{ float: left;}   
} 
.modalDialog_contentDivSave{ left: 50% !important; margin-left: -325px !important; }
</style>
	</head>
	<?php
    unset($_SESSION['timesheet_token']);
    $_SESSION['timesheet_token'] = md5(session_id() . time()); 
	?>
	<body ondragstart="return false" draggable="false"> 

	<form name="sheet" id="sheet" action="<?php echo $action_url; ?>" method="post" enctype="multipart/form-data">
		<input type="hidden" name="timesheet_token" value="<?php echo $_SESSION['timesheet_token'] ?>" /> 
		<input type="hidden" name="inoutflag" value="TimeInTimeOut">
		<input type="hidden" name="aa" id="aa" value="">
		<input type="hidden" name="hours" id="hours" value="">
		<input type="hidden" name="task" id="task" value="">
		<input type="hidden" name="client" id="client" value="">
		<input type="hidden" name="thours" id="thours" value="">
		<input type="hidden" name="othours" id="othours" value="">
		<input type="hidden" name="dbhours" id="dbhour" value="">
		<input type="hidden" name="billable" id="billable" value="">
		<input type="hidden" name="hourstype" id="hourstype" value="">
		<input type="hidden" name="class_type" id="class_type" value="">
		<input type="hidden" name="hdnDate" id="hdnDate" value="" >
		<input type="hidden" name="getdates" id="getdates" value="">
		<input type="hidden" name="taskdetails" id="taskdetails" value="">

		<input type="hidden" name="efrom1" id="efrom1" value="<?php echo $efrom1; ?>">
		<input type="hidden" name="sdates" id="sdates" value="<?php echo $servicedateto;?>">
		<input type="hidden" name="timeSubmitFlag" id="timeSubmitFlag" value="<?php echo $timeSubmitFlag; ?>">
		<input type="hidden" name="addr1" id="addr1" value="<?php echo $sno; ?>">
		<input type="hidden" name="selfCompany" id="selfCompany" value="<?php echo $ClVal; ?>">
		<input type="hidden" name="module" id="module" value="<?php echo $module; ?>">
		<input type="hidden" name="val" id="val" value="<?php echo $val; ?>">
		<input type="hidden" name="statval" id="statval" value="<?php echo $statval; ?>">
		<input type="hidden" name="valtodate" value="<?php echo $valtodate; ?>">
		<input type="hidden" name="selfserClient" value="<?php echo $ClVal; ?>">
		<input type="hidden" name="checking_from" id="checking_from" value="<?php echo $servicedate; ?>">
		<input type="hidden" name="checking_to" id="checking_to" value="<?php echo $servicedateto; ?>">
		<input type="hidden" name="rowcou" id="rowcou" value="<?php echo $rowcou; ?>">
		<input type="hidden" name="efrom1" id="efrom1" value="<?php echo $efrom1; ?>">
		<input type="hidden" name="max_upload" id="max_upload" value="<?php echo $max_upload; ?>">
		<input type="hidden" name="ts_status" id="ts_status" value="<?php echo $ts_status; ?>">
		<input type="hidden" name="edit_mode" id="edit_mode" value="<?php echo $mode; ?>">
		<input type="hidden" name="timedata" id="timedata" value="<?php echo html_tls_specialchars(addslashes($timedata),ENT_QUOTES);?>">
		<input type="hidden" name="empnames_oldvalue" id="empnames_oldvalue" value="">
		<!-- added hidden input field to get the client value in post Request after form submit-->
		<input type="hidden" name="clientVal" id="clientVal" value="<?php echo $clival; ?>" >
		<input type="hidden" name="reg_hours_max_row_id" id="reg_hours_max_row_id" value="0">
		<input type="hidden" name="ovt_hours_max_row_id" id="ovt_hours_max_row_id" value="">
		<input type="hidden" name="ovt_hours_max_row_exists" id="ovt_hours_max_row_exists" value="0">
		<input type="hidden" name="ovt_hrs_in_reg_max_row_id" id="ovt_hrs_in_reg_max_row_id" value="0">
		<input type="hidden" name="deleted_rowids" id="deleted_rowids" value="">
		<input type="hidden" name="new_rowids" id="new_rowids" value="">
		<input type="hidden" name="reg_max_hours" id="reg_max_hours" value="<?php echo $weekrule_maxregular;?>">
		<input type="hidden" name="ovt_max_hours" id="ovt_max_hours" value="<?php echo $weekrule_maxovertime;?>">
		<input type="hidden" name="ruletype_flag" id="ruletype_flag" value="<?php echo $ruletype_flag; ?>">
		<input type="hidden" name="prev_total_hours" id="prev_total_hours" value="<?php echo $prev_total_hours; ?>">
	<input type="hidden" name="rowid_hours_before_submit[]" id="rowid_hours_before_submit" value="<?php echo $rowid_hours_before_submit;?>">
		<!--Timesheet Grid optimization--added one input hidden field-->
                <input type="hidden" name="TimesheetGridType" id="TimesheetGridType" value="<?php echo $timeopttype; ?>" >
		<!-- Allowed duplicate timesheet if lockdown_flag is "allow_duplicate" -->
		<input type="hidden" name="lockdown_flag" id="lockdown_flag" value="<?php echo $lockdown_flag; ?>" >
		<input type="hidden" name="ess_user" id="ess_user" value="<?php echo $ess_user; ?>" >
		<input type="hidden" name="overwrite_weekend_rule" id="overwrite_weekend_rule" value="<?php echo $overwrite_weekend_rule; ?>">
		
		<div id="dynsndiv" style="display:none;"></div>
		<div id="main">
			<table width="100%" cellpadding="0" cellspacing="0" border="0" class="ProfileNewUI CustomTimesheetNew" align="center">
			<div id="content">
				<tr>
					<td style="position:relative">
            				<div class="CustTimeDateRangeT">
						<table width="100%" cellpadding="2" cellspacing="0" border="0"  class="ProfileNewUI SummaryTopBg">
						<tr>
						<?php
						if ($ruletype_flag == 'weekrule' && (int)$weekrule_maxhoursaday != 24 && $wk_ruleflag == 'Y') {
						?>
							<td><font class="modcaption"><?php echo $title; ?></font></td>
							<td align="right" style="padding-left:90px;"><font class="afontstylee" style="color:red;"><b>NOTE</b> : Week Rule Enabled. You are allowed to enter <?php echo $weekrule_maxhoursaday;?> hours per day.</font></td>
						<?php
						} else {
						?>
							<td><font class="modcaption TMEPadL-0 CustTimeSheetHed"><?php echo $title; ?></font></td>
						<?php
						}
						?>
							<td align="left" valign="top" rowspan="2" class="TMEPadR-10"><div style="float: right"><div class="FontSize-16"><?php if($_GET['mode'] == 'edit') { ?>Edit <?php } else { ?> Create a <?php } ?>  Time Sheet From&nbsp;</div><div class="TMEDateBg"><input type="text" size="10" class="afontstyle" maxlength="10" name="servicedate" id="servicedate" value="<?php echo $servicedate;?>" tabindex="1"><script language='JavaScript'>new tcal ({'formname':window.form,'controlname':'servicedate'});</script>&nbsp;To&nbsp;<input type="text" size="10"  maxlength="10" class="afontstyle" name="servicedateto" id="servicedateto" value="<?php echo $servicedateto;?>" tabindex="2"><script language='JavaScript'>new tcal ({'formname':window.form,'controlname':'servicedateto'});</script>&nbsp;</div><div class="TMEDateViewBtn" ><a href=javascript:DateCheck('servicedate','servicedateto')> <i class="fa fa-eye fa-lg"></i> view</a></div></div></td>
						</tr>
						<?php
						if($_GET['module'] != 'MyProfile' && $mode != 'edit' && $asgnflag)
						{
						?>
						<tr>
							
							<td style="background: #f6f7f9 !important;background-color: #f6f7f9 !important" valign="top" colspan="2"><span class="FontSize-16 TMEPadR-10 TMEResp">Select an Employee to fill the Timesheet:</span>
			    			
			    			<?php if($mod_val == 'Accounting'){ ?>
					        <select class="employees" id="empnames" name="empnames" onChange="javascript:getEmp()" style="width:200px !important"><option value='<?php echo $emp_uname?>'><?php echo $ename; ?></option></select>
					        <?php }?>

					        <?php if($mod_val == 'Client'){ ?>
					        <select id="empnames" name="empnames" onChange="javascript:getEmp()" class="select2-offscreen TMEmpWid FontSize-14 employees" tabindex="3"><?php echo $emp_drop_down; ?></select>
					    	<?php }?>
			        		

			        		

		        </td>
						</tr>
						<?php
						}
						else if($_GET['module'] == 'MyProfile' && $mode != 'edit')
						{
						?>
						<tr>
							<td valign="top" colspan="2"><input type="hidden" name="empnames" id="empnames_myprofile" value="<?php echo $employees;?>"></td>
						</tr>
						<?php	
						}
						else if($_GET['module'] != 'MyProfile' && $mode == 'edit')
						{
						?>
						<tr>
							<?php $addr = ($addr!='') ? $addr : $cun ; ?>
							<td valign="top" colspan="2">
								<input type="hidden" name="empnames" id="empnames_myprofile" value="<?php echo $empnames;?>">
								<input type="hidden" name="addr" value="<?php echo $addr;?>">
							</td>
						</tr>
						<?php	
						}
						else if($_GET['module'] == 'MyProfile' && $mode == 'edit')
						{
							if($empnames == '')
							{
								$empnames_edit = $empUsernames;
							}
							else
							{
								$empnames_edit = $empnames;
							}
						?>
						<tr>
							<td valign="top" colspan="2"><input type="hidden" name="empnames" id="empnames_myprofile" value="<?php echo $empnames_edit;?>"></td>
						</tr>
						<?php
						}
						?>
						</table>
                        			</div>
					</td>
				</tr>
			</div>

			<table width=100% cellpadding=0 cellspacing=0 border=0 style="margin-bottom:5px;" align="center" class="ProfileNewUI">
				<div id="topheader" >
				<tr class="NewGridTopBg">
				<?php
				$elements	= getValidAssgnElements($new_user,$rowcou,$elements,$thisday,$thisdayto,$condCk_comp,$showEmplyoees,$db,$module);
				$rowcou		= count($elements);
				$heading	= "time.gif~Create&nbsp;Timesheet";

				if ($mode == 'edit') {

					$heading="time.gif~Edit&nbsp;Timesheet";
				}

				//To build the selected dates string
				$service_dates_str = get_service_dates_str($servicedate,$servicedateto);
				
				//$heading="time.gif~Create&nbsp;Timesheet";
				$objEmpMenu->showHeadingStripTimesheet($name,$link,$heading,'TITO',$service_dates_str);
				
				?>
				</tr>
				</div>
			</table>

			<div id="grid_form" style="width:100%;">

			<?php $objTimeInTimeOut->getAssignments($empnames, '', $servicedate, $servicedateto, '0'); ?>

			<table id="MainTable" border="0" cellspacing="0" cellPadding="2" style="width:100%;" align="center" class="ProfileNewUI CustomTimesheetTh CustomTimesheetInput TimeSheetContM" >
				<tbody>
				<?php
				$tito_headers	= array('Date', 'Assignments', 'Time&nbsp;In', 'Time&nbsp;Out', 'Lunch/Break', 'Time&nbsp;In', 'Time&nbsp;Out');

				if (MANAGE_CLASSES == 'Y') {

					$tito_headers	= array('Date', 'Assignments', 'Class', 'Time&nbsp;In', 'Time&nbsp;Out', 'Lunch/Break', 'Time&nbsp;In', 'Time&nbsp;Out');
				}

				$dates_array	= $objTimeInTimeOut->buildDatesdropdown($timesheet_date_arr, $servicedate, $servicedateto, true);

				$f_totalhours		= 0.00;
				$reg_total_hours	= 0.00;
				$ovt_total_hours	= 0.00;
				$dbt_total_hours	= 0.00;

				if ($mode == 'edit') {

					$objTimeInTimeOut->getAssignments($empnames, '', $servicedate, $servicedateto, '0');

					echo $objTimeInTimeOut->buildTimeInTimeOutHeaders($tito_headers, $objTimeInTimeOut->assignments);
					$ratecountm=	$editrate = $objTimeInTimeOut->getRateTypesForAllAsgnnames($objTimeInTimeOut->assignments, true);

					$Cval	= 0;
					$cond	= '';
					$range	= 'no';

					if ($module == 'Client') {

						require_once('selfServicePref.php');
						
						$tsConditions 		= buildTimesheetConditions($userSelfServicePref[7]);
						$tsConditionsArr 	= explode("|",$tsConditions);
						
						$tsBillContactCon	= $tsConditionsArr[0];
						$conJoin 		= str_replace("hrcon_jobs.","hj.",$tsConditionsArr[1]);
						$condCk			= $tsConditionsArr[2];
						$conJoinBill		= $tsConditionsArr[3];

						$Cval = $objTimeInTimeOut->getClientId($username);
						if($Cval != $clival) // means billing contact related timesheets
						{
							$Cval 		= $clival;
							$condCk		= "";
							$conJoin	= "";
						}
						//$Cval = $objTimeInTimeOut->clientcheckingArr[$empnames];

						if (strpos($userSelfServicePref[7],"+6+")) {

							$bill		= " t2.billable !='' AND t2.billable !='No' AND ";
							$bill_rates	= " AND th.billable !='' AND th.billable !='No' ";
						}

						$cond	= ' AND '.$condCk.$bill." t1.username='".$empnames."' AND t2.parid='".$sno."' AND t2.client IN (".$Cval.")";
					}

					$rates_total_hours	= $objTimeInTimeOut->getTotalHoursForRates($sno, $ts_status, $bill_rates);

					if (!empty($rates_total_hours)) {

						foreach ($rates_total_hours as $key => $object) {

							if ($object->rate == 'rate1') {

								$reg_total_hours	= $object->rates_total;
							}

							if ($object->rate == 'rate2') {

								$ovt_total_hours	= $object->rates_total;
							}

							if ($object->rate == 'rate3') {

								$dbt_total_hours	= $object->rates_total;
							}
						}
					}

					$sql	= "SELECT
								SUM(t2.hours) AS totalHrs, t1.sdate servicedate, t1.edate servicedateto, t1.issues, t2.username,
								t2.sdate, t2.edate, t2.client, t2.task, GROUP_CONCAT(CONCAT(t2.hourstype, '|', t2.hours, '|', billable)) AS rtype,
								t2.assid, t2.classid, GROUP_CONCAT(t2.sno) tssno, GROUP_CONCAT(t2.hourstype) AS rate,
								GROUP_CONCAT(concat(t2.sno,'-',t2.hourstype)) AS snorate, t2.qbid, t1.astatus, t2.rowid
							FROM
								par_timesheet t1 INNER JOIN timesheet_hours t2 ON t1.sno = t2.parid
								LEFT JOIN hrcon_jobs AS hj ON t2.assid = hj.pusername
								LEFT JOIN staffacc_cinfo sc ON t2.client = sc.sno
								INNER JOIN emp_list el ON el.username = t1.username
								LEFT JOIN users u ON u.username = t2.auser ".$conJoin." 
							WHERE
								t1.sno='".$sno."' AND t2.status='".$ts_status."' AND t2.payroll='' ".$cond."
							GROUP BY
								t2.rowid";

					$result	= mysql_query($sql, $db);
					
					$tab_index = 5;

					while ($row = mysql_fetch_assoc($result)) {

						$rowCount	= $row['rowid'];
						$tssno_str	= $row['tssno'];

						$tssno_strrate	= $row['snorate'];
						$tsrate_str		= $row['rate'];

						$explode_rate		= explode(',',$tsrate_str);
						$explode_sno		= explode(',',$tssno_str);
						$explode_snorate	= explode(',',$tssno_strrate);

						foreach($editrate  as $key => $valu) {

							if (in_array($valu,$explode_rate)) {

								foreach($explode_snorate as $rateval) {

									$abcarr	= explode('-', $rateval);

									if ($abcarr[1] == $valu) {

										$edit_string	.= "|".$row['astatus']."|".$abcarr[0]."||".$row['classid']."|".$row['qbid']."#^#";
										$edit_string_1	.= "|".$row['astatus']."|".$abcarr[0]."||".$row['classid']."|".$row['qbid']."#^#";
									}
								}

							} else {

								$edit_string	.= "|||||#^#";
								$edit_string_1	.= "|||||#^#";
							}
						}

						$explodedDatesArr	= explode(" ", $dates_array[$rowCount]);
						$sdate	= date('m/d/Y', strtotime($row['sdate']));

						if ($row['edate'] != '0000-00-00') {

							$edate = date('m/d/Y', strtotime($row['edate']));
							$range = 'yes';

						} else {

							$edate = $sdate;
						}
						if($module == 'Client')
						{
							$Cval = $objTimeInTimeOut->clientcheckingArr[$row['username']]; // Client values over writing to load all assignment in the drop down
						}
						echo $objTimeInTimeOut->getCreateTimesheetHTML($row['username'], $row['assid'], $row['rtype'], $row['task'], $dates_array, $sdate, $edate, $row['classid'], $rowCount, $range, $row['tssno'], $edit_string, $row['rowid'],$module,$row['totalHrs'], $sno, $tab_index, $Cval);

						$edit_string_1.="^^";

						$f_totalhours = $f_totalhours+$row['totalHrs'];
						
						$tab_index += 8;
					}

					$allsnos	= implode(',', $objTimeInTimeOut->mystr);
					
					$maxRowId = $objTimeInTimeOut->getMaxRowId($sno);
					$rowCount = $maxRowId;

				} else {

					if ($asgnflag) {

						if ($module == 'Client') {
							$Cval = $objTimeInTimeOut->clientcheckingArr[$empnames];
						}
						
						$objTimeInTimeOut->getAssignments($empnames, '', $servicedate, $servicedateto, '0');

						echo $objTimeInTimeOut->buildTimeInTimeOutHeaders($tito_headers, $objTimeInTimeOut->assignments);

						$ratecountm	= $objTimeInTimeOut->getRateTypesForAllAsgnnames($objTimeInTimeOut->assignments, true);

						$rowCount		= 0;
						$enteredData	= '';
						
						$tab_index = 7;

						foreach ($timesheet_date_arr as $key=>$val) {

							$timesheet_start_date_day	= explode(' ', $val);

							if ($objTimeInTimeOut->checkAssignmentExists($empnames, $timesheet_start_date_day[0], $timesheet_start_date_day[0],$module,$Cval)) {

								echo $objTimeInTimeOut->getCreateTimesheetHTML($empnames, $asgnid, '', '', $dates_array, $timesheet_start_date_day[0], $timesheet_start_date_day[0], $classid, $rowCount,'','','','',$module, '', '', $tab_index, $Cval);
								$rowCount++;
							}

							if ($rowCount > 7) {

								break;
							}
							
							$tab_index+=8;
						}
					}
					else
					{
					?>
						<tr><td colspan='3' align='center'>No Assignments Found<br /></td></tr>
					<?php
					}
				}

					$colspan	= 2;

					if (MANAGE_CLASSES == 'Y') {

						$colspan	= 3;
					}

					if ($asgnflag) {

						echo $objTimeInTimeOut->getTotalHoursHTML($reg_total_hours, $ovt_total_hours, $dbt_total_hours, $f_totalhours);
					?>
						<input type="hidden" name="f_totalhrs" id="f_totalhrs" value="<?php echo $f_totalhours;?>" >
						<input type="hidden" name="allsnos" id="allsnos" value="<?php echo $allsnos;?>">
						<input type="hidden" name="edit_string_new" id="edit_string_new" value="<?php echo $edit_string; ?>">
						<input type="hidden" name="edit_string_new_1" id="edit_string_new_1" value="<?php echo $edit_string_1; ?>">
						<input type="hidden" name="edit_sno_ids" id="edit_sno_ids" value="">
						<input type="hidden" name="edit_sno_ids_final" id="edit_sno_ids_final" value="">
						<input type="hidden" name="delete_sno_ids" id="delete_sno_ids" value="">
						<input type="hidden" name="dynrowcount" id="dynrowcount" value="<?php echo $rowCount;?>" >
						<input type="hidden" name="colcount" id="colcount" value="<?php echo count($ratecountm);?>" >
					<?php
					}
					?>
					</tbody>
				</table>
			</div>
			</table>
		</div>
		<input type="hidden" name="hiddenBillable" id="hiddenBillable" value="<?php echo implode(",", $objTimeInTimeOut->hiddenBillable[0]); ?>">
		<input type="hidden" name="tabindexcount" id="tabindexcount" value="<?php echo $tab_index; ?>">
		<input type="hidden" name="client_id" id="client_id" value="<?php echo $Cval;?>" >
		<?php
		if($asgnflag)
		{
		?>
		<div class="timeRemarks">
		<table width=99% cellpadding=3 cellspacing=0 border=0 align=" center">
			<?php
			if ($module == 'Accounting') {

				if ($mode == 'edit') {

					if (!empty($issues)) {
			?>
						<tr>
							<td valign="top"><font class="afontstylee">&nbsp;Remarks</font></td>
							<td class="afontstylee"><?php echo html_tls_specialchars(stripslashes($issues),ENT_QUOTES);?></td>
							<input type="hidden" name="issues" id="issues" value="<?php echo html_tls_specialchars(stripslashes($issues),ENT_QUOTES); ?>">
						</tr>
			<?php
					}
			?>
					<tr><td colspan="2">&nbsp;</td></tr>
					<tr>
						<td valign="top"><font class="afontstylee">&nbsp;Notes</font></td>
					</tr>
            		<tr>
						<td>
							<textarea cols="95" rows="3" wrap="virtual" id="issues" name="notes" tabindex="<?php echo $tab_index=$tab_index+1;?>"><?php echo html_tls_specialchars(stripslashes($notes),ENT_QUOTES);?></textarea>
						</td>
					</tr>
			<?php
				} else {
			?>
					<tr>
						<td valign="top"><font class="afontstylee">&nbsp;Remarks</font></td>
					</tr>
            		<tr>
						<td>
							<textarea cols="95" rows="3" wrap="virtual" id="issues" name="issues" tabindex="<?php echo $tab_index=$tab_index+1;?>"></textarea>
						</td>
					</tr>
			<?php
				}

			} elseif ($module == 'MyProfile') {
			?>
				<tr>
					<td valign=top><font class="afontstylee">&nbsp;Remarks</font></td>
				</tr>
                <tr>
					<td>
						<textarea cols="95" rows="3" wrap="virtual" name="issues" tabindex="<?php echo $tab_index=$tab_index+1;?>"><?php echo html_tls_specialchars(stripslashes($issues),ENT_QUOTES); ?></textarea>
					</td>
				</tr>
			<?php
			} elseif ($module == 'Client') {

				if ($mode == 'edit') {

					if (!empty($issues)) {
			?>
						<tr>
							<td valign="top"><font class="afontstylee">&nbsp;Remarks</font></td>
						</tr>
            			<tr>
							<td class="afontstylee"><?php echo html_tls_specialchars(stripslashes($issues),ENT_QUOTES);?></td>
							<input type="hidden" name="issues" id="issues" value="<?php echo html_tls_specialchars(stripslashes($issues),ENT_QUOTES); ?>">
						</tr>
			<?php
					}
			?>
					<tr>
						<td valign="top"><font class="afontstylee">&nbsp;Notes</font></td>
					</tr>
            		<tr>
						<td>
							<textarea cols="95" rows="3" wrap="virtual" name="notes" tabindex="<?php echo $tab_index=$tab_index+1;?>"><?php echo html_tls_specialchars(stripslashes($notes),ENT_QUOTES);?></textarea>
						</td>
					</tr>
			<?php
				} else {
			?>
				<tr>
					<td valign="top"><font class="afontstylee">&nbsp;Remarks</font></td>
					</tr>
            		<tr>
					<td>
						<textarea cols="95" rows="3" wrap="virtual" name="issues" tabindex="<?php echo $tab_index=$tab_index+1;?>"></textarea>
					</td>
				</tr>
			<?php
				}
			}
			?>
			<tr>
				<td colspan=2><font class=bstrip>&nbsp;</font></td>
			</tr>
			<?php
			if ($mode == 'edit') {
			?>
			<tr>
				<td colspan="2">
					<?php
					echo $objTimeInTimeOut->getTimesheetAttachments($sno, 'edit');
					?>
				</td>
			</tr>
			<?php
			}
			?>
			<tr>
				<td colspan="2"><font class="afontstylee">&nbsp;Upload Time Sheet File</font>
				<input type="file" name="timefile" id="timefile" style="font-family:Arial;font-size:8pt;border:none;" size="65" tabindex="<?php echo $tab_index=$tab_index+1;?>"/></td>
			</tr>
		</table>
	</div>
		<?php
		}
		?>
		<div id="topheader" style=";margin-top:10px;" class="NewGridBotBg">
			<table width="99%" align="center">
			<tr>
				<?php //$objEmpMenu->showHeadingStrip1($name,$link,$heading); ?>
			</tr>
			</table>
		</div>

		<div align="center" id="SaveAlert" class="modalDialog_contentDiv" style="height:188px; width:650px; border:0px thick #474c4f; display:none;">
			<table style="width:100%;" border="0">
				<tr valign="middle">
					<td width="99%" style="text-align:center;">
					<font style="font-family:Arial, Helvetica, sans-serif; size=12px"; ></font><br /><br /><img src='/BSOS/images/preloader.gif' align=middle />
					</td>
				<tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert('-1');" class="buttonAssoc" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr>
				</tr>
			</table>
		</div>
		<div id="disclaimer_content" style="display: none;"><?php echo $dis_message; ?></div>
		</form>
	</body>
</html>
<script type="text/javascript">
	$('a').click(function(){		
		var me = $(this);
		me.attr("id", "timesubmit");

		$('#timesubmit').bind('keydown', function(e) {
		if (e.keyCode == 13) {
			e.preventDefault();
			}
		});		
	});

	$(document).ready(function(){
		$hide_billable_checkbox = $("#hide_billable_checkbox").val();
		if($hide_billable_checkbox){
			$('.container-chk').hide();
		}
		if(module != "MyProfile" && mode != "edit" && module!= 'Accounting') {
			$("#empnames").select2();
		}
		if(module != "MyProfile" && mode != "edit" && module=='Accounting' && module!='Client') {
			var assignSdate = '<?php echo $servicedate;?>';
			var assignEdate = '<?php echo $servicedateto;?>';
			var pageModule = '<?php echo $module;?>';
			$("#empnames").select2({
	    
		        //placeholder: "Select an Employee",
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
						  getModule : module,
						  getServicedate :'<?php echo $servicedate;?>',  
						  getServicedateto :'<?php echo $servicedateto;?>',
						  selectedEmployee:customersids,
						  getEmployeeSearchVal: params
						}
						return queryParameters;
					},
					initSelection: function(element, callback) {
						alert(element);
					    callback({ id: element.val(), text: element.attr('data-init-text') });
					},
					results: function (data, params) {
					    params.page = params.page || 1;
					    return {
					        results: data.results
					        
					    };
					},
					cache: true
				},
		            
		        language: {
			       	noResults: function(){
			           return "No Employee Found";
			       	},
			       /*	searching: function(){
				        return "<span><i class='fa fa-spin fa-spinner'></i>Searching Please Wait</span>"
				    }*/
			   	},
		        escapeMarkup: function (m) {
		        	return m; 
		        }
		    });
		}

});
</script>