<?php
	require("global.inc");
	require_once($akken_psos_include_path.'commonfuns.inc');
	require_once('timesheet/class.Timesheet.php');
	global $db,$accountingExport;
	$efrom1 = getFromEmailID($username);
	require("Menu.inc");
	$menu=new EmpMenu();
	 
	$timesheetObj = new AkkenTimesheet($db);
	$rate_types_count = 0;
	$mod_val = $_GET['module'] == ''?$module:$_GET['module'];
		
	$edit_mode = ($_GET['mode']==TRUE) ? 'edit': 'create';
	if($mode == 'edit')
	{
		$sql = "SELECT MIN(t1.sdate) servicedate, MAX(t1.edate) servicedateto , t2.username, t1.issues, t1.notes FROM par_timesheet t1 INNER JOIN timesheet_hours t2 ON t1.sno = t2.parid WHERE t1.sno = '".$sno."' GROUP BY t1.sdate";
		$result = mysql_query($sql, $db);
		$row = mysql_fetch_row($result);
		
		$time_fromdate_dbVal = date('m/d/Y', strtotime($row[0]));
		$time_todate_dbVal = date('m/d/Y', strtotime($row[1]));
		
		if($servicedate !='')
			$servicedate = $servicedate;
		else
			$servicedate = date('m/d/Y', strtotime($row[0]));

		if($servicedateto != '')
			$servicedateto = $servicedateto;
		else
			$servicedateto = date('m/d/Y', strtotime($row[1]));

		$empnames 	= $row[2];
		$issues 	= $row[3];
		$notes 		= $row[4];
	}
	
	$timeSubmitFlag = 0;
	if($time_fromdate_dbVal != $servicedate || $time_todate_dbVal != $servicedateto)
		$timeSubmitFlag = 1;
	
	$date = date('Y-m-d');
	
	if(isset($servicedate) && isset($servicedateto))
	{
		$timesheet_date_arr = $timesheetObj->GetDays($servicedate, $servicedateto);
	}
	else
	{
		$timesheet_date_arr		= $timesheetObj->getWeekdays($date);
		$timesheet_start_date		= explode(" ", $timesheet_date_arr[0]);
		$timesheet_end_date		= explode(" ", $timesheet_date_arr[6]);
		$servicedate			= $timesheet_start_date[0];
		$servicedateto			= $timesheet_end_date[0];
	}
	
	$employees = $timesheetObj->getEmployees($module, $username, $servicedate, $servicedateto);	
	if($empnames == '')
	{
		$empnames = $timesheetObj->new_first_user;
	}

	$emp_drop_down = $timesheetObj->buildEmpList($employees, $empnames);
	$asgnflag = $timesheetObj->checkAssignmentExists($empnames, $servicedate, $servicedateto);
	if($module=='Accounting' && $module !='Client'){
		
		$uname = $_POST['empnames'];
		$emp_uname = ($uname== '')?$empnames:$uname;
		$query="select name,sno from emp_list where username='".$emp_uname."'";
		$res=mysql_query($query,$db);
		$myrow=mysql_fetch_row($res);
		$ename = $myrow[1].'-'.$myrow[0];
	}
	
	if($asgnflag) {
		if($module=='Accounting'){
			$name=explode("|","fa fa-plus-circle~Add Row|fa fa-envelope~Submit|fa-ban~Cancel");
			$link=explode("|","javascript:void(0)|javascript:onClick=validate('submit');|javascript:self.close()");
		}
		elseif($module=='MyProfile'){
			if($ts_status == "Rejected"){
				$name=explode("|","fa fa-plus-circle~Add Row|fa fa-envelope~Submit|fa-ban~Cancel");
				$link=explode("|","javascript:void(0)|javascript:onClick=validate('submit');|javascript:self.close()");
			}else{
				$name=explode("|","fa fa-plus-circle~Add Row|fa fa-floppy-o ~Save|fa fa-envelope~Submit|fa-ban~Cancel");
				$link=explode("|","javascript:void(0)|javascript:onClick=validate('save');|javascript:onClick=validate('submit');|javascript:self.close()");
			}
			
		}
		elseif($module=='Client'){
			$name=explode("|","fa fa-plus-circle~Add Row|fa fa-envelope~Submit|fa-ban~Cancel");
			$link=explode("|","javascript:void(0)|javascript:onClick=validate('submit');|javascript:self.close()");
		}
	} else {
		if($module=='Accounting'){
			$name=explode("|","fa fa-plus-circle~Add Row|fa-ban~Cancel");
			$link=explode("|","javascript:void(0)|javascript:self.close()");
		}
		elseif($module=='MyProfile'){
			$name=explode("|","fa-ban~Cancel");
			$link=explode("|","javascript:self.close()");
		}
		elseif($module=='Client'){
			$name=explode("|","fa fa-plus-circle~Add Row|fa-ban~Cancel");
			$link=explode("|","javascript:void(0)|javascript:self.close()");
		}
	}
	//* Disclaimer Content */

	$lockdown_flag = "allow_duplicate";	
	$ess_user = "NO";

	if($module=='MyProfile'){
		// Check user preferences
		$userpref_que = "select myprofile from sysuser where username='".$username."'";
		$userpref_res = mysql_query($userpref_que,$db);
		$userSelfServicePref = mysql_fetch_row($userpref_res);	
		$disclaimer_flag = false;
		
		if(strpos($userSelfServicePref[0],"+20+") || strpos($userSelfServicePref[0],"+20"))
		{
			$dis_selqry = "select sno,dis_message from css_ess_disclaimer where username = '".$username."' and acctype='SSU'";
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
		
		if(chkLoginUserIsEss($username))
		{
			//echo "ESS user.";
			$ess_user	= "YES";
			$sel_pref_query	= "SELECT ess_lockdown FROM cpaysetup WHERE status='ACTIVE'";
			$res_pref_query	= mysql_query($sel_pref_query, $db);
	
			if (mysql_num_rows($res_pref_query) > 0) {
	
				$row_pref_query = mysql_fetch_object($res_pref_query);
				$ess_lockdown_pref = $row_pref_query->ess_lockdown;

				if(strpos($ess_lockdown_pref,'3')=== false) //if not availabe in the preferences
				{
					$lockdown_flag = "dont_allow_duplicate";
				}
			}
		}
	}
	$hide_billable_checkbox = false;
	if($module == 'MyProfile')
	{
		$hide_billable_checkbox = true;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" >
<title>Timesheet</title>
<style type="text/css">
a {
    display: inline-block;
    position: relative;
	color:#474c4f;
}
.caption {
    display: none;
    position: absolute;
    top:15;
	 font-family: Arial;
    font-size: 8pt;
    font-style: normal;
    left: 20;
    right: 0;
  
    color:#474c4f;
   
	
}

input[readonly="readonly"]
{
    background-color:#dddddd;
}

.multiselect{
border: 1px solid red ;
}

a:hover .caption {
    display: inline-block;
}
.hthbgcolorr {
    background-color: #78DAF7;

}
.afontstylee .rates{
	width:45px !important;
}
.grid_forms {width:auto; overflow-x:scroll; position:relative;}



.afontstylee {
	color: #474c4f;
	font-family: Arial;
	font-size: 8pt;
	font-style: normal;
   /* line-height: 12px; */
	padding-left:7px;
	padding:0px;


}
.modcaption { FONT-SIZE: 12pt; FONT-FAMILY: Arial; FONT-WEIGHT: bold; COLOR: #F02933; }
.afontstyle1 {
	color: #474c4f !important;
	font-family: Arial;
	font-size: 8pt;
	font-style: normal;
	line-height: 19px;
	padding-left: 7px;
}

#MainTable tr.tr_clone td, tr.weekly_row td
{
	padding:0px 2px 12px 2px;
	white-space: nowrap !important;
}
.textwrampnew { white-space: normal !important}
#MainTable
{
padding-top:0px;
}
 

/*	{
	    background-color:#f6f7f9;
	    font-family: Arial;
		font-size:14px;
		font-style: normal;
		line-height:24px;
		border-top:solid 1px #ccc;
	} */
.addtaskBtn
{
	width:100px; 
	color: black;
	line-height:26px;-webkit-line-height:33px;-o-line-height:33px;
	display: inline-block;font-family: Arial;font-size: 8pt; 
	font-style: normal;cursor:pointer;
}

.typeh1
{
	font-size:11px;
	font-weight:bold;
	padding-left:10px
}
.hthbgcolor {background-color:#0193c9;}
.ajaxloadingshow
{
	display: block;
	position: absolute;
	top: 200px;
	left: 500px;
	background: white;
	border: 1px solid #474c4f;
	text-align: center;
}
.ajaxloadinghide
{
	display: none;
	position: absolute;
	top: 200px;
	left: 500px;
	background: white;
	border: 1px solid #474c4f;
	text-align: center;
}
.alert-w-chckbox-chkbox {
	MARGIN-TOP: 0px;
	MARGIN-LEFT: 75px;
}
.tsDiv{
    text-align:right !important;
	margin-right: 15px !important;
	text-align: left !important;
   <?php if($module != 'Client'){ ?>
	width: 70px;
	<?php
	} ?>
}
.tsrates{
/*text-align:right !important;*/
}
 #MainTable th:nth-child(2),#MainTable th:nth-child(3){
    text-align:left !important;
} 
 .totsDiv{
    width:100px !important;
	text-align:left;
}
#dynsndiv{
	position:fixed !important;
}
</style>
<?php
if($disclaimer_flag)
{
?>
<style>
.alert-w-chckbox-chkbox-time-exe-moz{
	height: 270px !important;
}
.alert-w-chckbox-chkbox-time-exe-ie{
	height: 267px !important;
}

@media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {
	#DHTMLSuite_modalBox_contentDiv{	
		height: 400px !important;
	}
	.alert-cntrbtns-time-exe{						
		margin: 15px 0 0 185px !important;
	}
}
@media screen and (-webkit-min-device-pixel-ratio:0){
	#DHTMLSuite_modalBox_contentDiv{	
		height: 400px !important;
	}
	.alert-cntrbtns-time-exe{
		margin: 7px 0 0 200px !important;
	}
}
@-moz-document url-prefix() {
	.alert-cntrbtns-time-exe{						
		margin: 6px 0 0 200px !important;
	}
}
</style>
<?php
}
?>
<script src="/BSOS/scripts/jquery-1.8.3.js"></script>
<script language="javascript" src="/BSOS/scripts/validate_uomTimesheet.js"></script>
<script src=/BSOS/scripts/date_format.js language=javascript></script>
<script type="text/javascript" src="/BSOS/popupmessages/scripts/popupMsgArray.js"></script>
<script type="text/javascript" src="/BSOS/popupmessages/scripts/popup-message.js"></script>
<link href="/BSOS/css/fontawesome.css" rel="stylesheet" type="text/css">
<link href="/BSOS/Home/style_screen.css" rel="stylesheet" type="text/css">
<link href="/BSOS/css/educeit.css" rel="stylesheet" type="text/css">
<link href="/BSOS/css/timeintimeout.css" rel="stylesheet" type="text/css">
<?php if($mod_val!='Accounting'){?>
	<script type="text/javascript" src="/BSOS/scripts/select2.js"></script> 
<?php }?>
<link rel="stylesheet" href="/BSOS/popupmessages/css/popup_message.css" media="screen" type="text/css">
<link rel="stylesheet" href="/BSOS/css/popup_styles.css" media="screen" type="text/css">
<link rel="stylesheet" href="/include/timesheet/css/timesheet.css" media="screen" type="text/css">
<link rel="stylesheet" href="/BSOS/css/merge.css" media="screen" type="text/css">
<!-- Custom Select Box Styles-->
<link rel="stylesheet" type="text/css" media="screen" href="/BSOS/css/customSelectNew.css"/>
<?php if($mod_val=='Accounting'){?>
<script type="text/javascript" src="/BSOS/scripts/select2_V4.0.3.js"></script>
<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/select2_V_4.0.3.css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/gigboardCustom.css">
<?php }?>
<script type="text/javascript" src="/BSOS/scripts/customSelectNew.js"></script> 
<script src=/BSOS/scripts/common_ajax.js></script>
<style>
.mloadDiv #autopreloader { position: fixed; left: 0; top: 0; z-index: 99999; width: 100%; height: 100%; overflow: visible; 
opacity:0.35;background:#000000 !important;}
.mloadDiv .newLoader{position:absolute; top:50%; left:50%;z-index:99999;margin-left:-67px;margin-top:-67px;width:150px;height:140px;}
.mloadDiv .newLoader img{border-radius:69px !important;}
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
</style>
<script type="text/javascript">
/* Added autopreloader function to show loading icon, until the page completely loads */
$(window).load(function(){	
	$('#autopreloader').delay(5000).fadeOut('slow',function(){$(this).remove();});
	$('.newLoader').delay(5000).fadeOut('slow',function(){$(this).remove();});
});
</script>
<script type="text/javascript" src="/BSOS/scripts/calendar.js"></script>
<link rel="stylesheet" type="text/css" href="/BSOS/css/calendar.css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/timeSheetselect2.css">
    
    <style type="text/css"> 
    
    @media only screen and (max-width: 1024px) {
.CustTimeSheetHed{ font-size: 14px !important; }
.SummaryTopBg{ padding-top: 0px;}
.FontSize-16{ font-size: 13px;}
.TMEmpWid{float: left; clear: both;}
.TMEResp{ float: left;}   
} 
    
    </style>
    
</head>
<?php
    unset($_SESSION['timesheet_token']);
    $_SESSION['timesheet_token'] = md5(session_id() . time()); 
?>
<body>
<div class="mloadDiv"><div id="autopreloader"></div>
<div class="newLoader"><img src="/BSOS/images/akkenloading_big.gif"></div>	
<form action=savetime.php name=sheet id=sheet method=post ENCTYPE="multipart/form-data">
<input type="hidden" name="timesheet_token" value="<?php echo $_SESSION['timesheet_token'] ?>" /> 
<input type="hidden" name="uomflag" value="UOM">
<input type=hidden name="timeSubmitFlag" id="timeSubmitFlag" value="<?php echo $timeSubmitFlag;?>">
<input type=hidden id="efrom1" name="efrom1" value="<?php echo $efrom1; ?>">
<input type=hidden name=aa id=aa value="">
<input type=hidden name=sdates id=sdates value="<?=$servicedateto?>">
<input type=hidden name=client id=client value="">
<input type=hidden name=task id=task value="">
<input type=hidden name=ts_status id=ts_status value="<?php echo $ts_status; ?>">
<input type=hidden name=thours id=thours value="">
<input type=hidden name=othours id=othours value="">
<input type=hidden name=dbhours id=dbhour value="">
<input type=hidden name=billable id=billable value="">
<input type=hidden name=val value="<?php echo $val;?>">
<input type=hidden name=valtodate value="<?php echo $valtodate;?>">
<input type="hidden" name="timedata" value="<?php echo html_tls_specialchars(addslashes($timedata),ENT_QUOTES);?>">
<input type="hidden" name="module" id="module" value="<?php echo $module;?>">
<input type=hidden name=selfserClient value="<?php echo $ClVal;?>">
<input type=hidden name=checking_from id="checking_from" value="<?php echo $servicedate;?>">
<input type=hidden name=checking_to id="checking_to" value="<?php echo $servicedateto;?>">
<input type=hidden name="selfCompany" value="<?php echo $ClVal; ?>">
<input type="hidden" name="hours" id="hours" value="" >
<input type="hidden" name="hourstype" id="hourstype" value="">
<input type="hidden" name="class_type" id="class_type" value="">
<input type="hidden" name="hdnDate" id="hdnDate" value="" >
<input type="hidden" name="getdates" id="getdates" value="">
<input type="hidden" name="taskdetails" id="taskdetails" value="">
<input type="hidden" name="edit_mode" id="edit_mode" value="<?php echo $mode ; ?>">
<input type="hidden" name="addr1" id="addr1" value="<?php echo $sno ; ?>">
<input type="hidden" name="max_upload" id="max_upload" value="<?php echo $max_upload ; ?>">
<input type="hidden" name="pref_disclaimer" id="pref_disclaimer" value="<?php echo $pref_disclaimer;?>">
<input type="hidden" name="clientVal" id="clientVal" value="<?php echo $clival; ?>" >
<!--Timesheet Grid optimization--added one input hidden field-->
<input type="hidden" name="TimesheetGridType" id="TimesheetGridType" value="<?php echo $timeopttype; ?>" >
<!-- Allowed duplicate timesheet if lockdown_flag is "allow_duplicate" -->
<input type="hidden" name="lockdown_flag" id="lockdown_flag" value="<?php echo $lockdown_flag; ?>" >
<input type="hidden" name="ess_user" id="ess_user" value="<?php echo $ess_user; ?>" >

<input type="hidden" name="activeDayWeekTab" id="activeDayWeekTab" value="" >
<input type="hidden" name="hide_billable_checkbox" id="hide_billable_checkbox" value="<?php echo $hide_billable_checkbox;?>" >
<div id='dynsndiv' style='display:none;'></div>
<div id="main" style="outline: none;">
<td valign=top align=center>
<table width=100% cellpadding="0" cellspacing="0" border=0 class="ProfileNewUI CustomTimesheetNew" align="center">
	<div id="content">
		<tr>
			<td style="position:relative">
			<div class="CustTimeDateRangeT">
				<table width=100% cellpadding=0 cellspacing=0 border=0 class="ProfileNewUI SummaryTopBg">
				<tr>
					<?php
						if($mode == 'edit')
						{
							$title = 'Edit&nbsp;Timesheet';
						}
						else
						{
							$title = 'Create&nbsp;Timesheet';
						}
					?>
					<td valign="top"><font class="modcaption TMEPadL-0 CustTimeSheetHed"><?php echo $title; ?></font></td>
					<td align="left" valign="top" rowspan="2" class="TMEPadR-10"><div style="float: right"><div class="FontSize-16 TMEPadT-10"><?php if($_GET['mode'] == 'edit') { ?>Edit <?php } else { ?> Create a <?php } ?> Timesheet From&nbsp;</div><div class="TMEDateBg"><input type=text size=10 class=afontstyle maxlength="10" name=servicedate id=servicedate value="<?php echo $servicedate;?>" tabindex="1"><script language='JavaScript'>new tcal ({'formname':window.form,'controlname':'servicedate'});</script><span class="FontSize-16 TMEPadLR-6">To</span><input type=text size=10  maxlength="10" class=afontstyle name=servicedateto id=servicedateto value="<?php echo $servicedateto;?>" tabindex="2"><script language='JavaScript'>new tcal ({'formname':window.form,'controlname':'servicedateto'});</script>&nbsp;</div><div class="TMEDateViewBtn" ><a href=javascript:DateCheck('servicedate','servicedateto')><i class="fa fa-eye fa-lg"></i> view</a></div></div></td>
				</tr>
				<?php
				if($_GET['module'] != 'MyProfile' && $mode != 'edit')
				{
				?>
				<tr>
					<td style="background: #f6f7f9 !important;background-color: #f6f7f9 !important" valign="top" colspan="2"><span class="FontSize-16 TMEPadR-10 TMEResp">Select an Employee to fill the Timesheet:</span>
						<?php if($mod_val == 'Accounting'){ ?>
					        <select class="employees" id="empnames" name="empnames" onChange="javascript:getEmp()" style="width:200px !important"><option value='<?php echo $emp_uname?>'><?php echo $ename; ?></option></select>
					        <?php }?>

					        <?php if($mod_val == 'Client'){ ?>
					        <select class="select2-offscreen TMEmpWid FontSize-14 employees" name="empnames" id="empnames" onChange="javascript:getEmp()" tabindex="3"><?php echo $emp_drop_down; ?></select>
					    	<?php }?>

			                	
		            </td>
				</tr>
				<?php
				}
				else if($_GET['module'] == 'MyProfile' && $mode != 'edit')
				{
				?>
				<tr>
					<td colspan="3"><input type="hidden" name="empnames" id="empnames_myprofile" value="<?php echo $employees;?>"></td>
				</tr>
				<?php	
				}
				else if($_GET['module'] != 'MyProfile' && $mode == 'edit')
				{
				?>
				<tr>
					<?php
					$addr = ($addr!='') ? $addr : $cun ;
					if($addr == "")
					{
						$uque	= "SELECT username FROM par_timesheet WHERE sno=$sno";
						$ures	= mysql_query($uque,$db);
						$urow	= mysql_fetch_row($ures);
						$addr	= $urow[0];
					}
					?>
					<td colspan="3"><input type="hidden" name="empnames" id="empnames_myprofile" value="<?php echo $addr;?>"><input type="hidden" name="addr" value="<?php echo $addr;?>"></td>
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
					<td colspan="3"><input type="hidden" name="empnames" id="empnames_myprofile" value="<?php echo $empnames_edit;?>"></td>
				</tr>
				<?php	
				}
				?>
				</table>
			</div>
			</td>
		</tr>
	</div>
	<!--added nagaraju shiva-->
	<table width=100% cellpadding=0 cellspacing=0 border=0  align="center">
	<div id="topheader" >
	<tr class="NewGridTopBg">
	<?php
	$elements = getValidAssgnElements($new_user,$rowcou,$elements,$thisday,$thisdayto,$condCk_comp,$showEmplyoees,$db,$module);
	$rowcou = count($elements);

	if($mode == 'edit')
	{
		 $heading="time.gif~Edit&nbsp;Timesheet";
	}else{
		 $heading="time.gif~Create&nbsp;Timesheet";
	}
	
	//To build the selected dates string
	$service_dates_str = get_service_dates_str($servicedate,$servicedateto);
	
	//$heading="time.gif~Create&nbsp;Timesheet";
	$menu->showHeadingStripTimesheet($name,$link,$heading,'UOM',$service_dates_str);
	
	?>
	</tr>

	</div>
	</table>
	<!--nagaraju shiva-->
	<div id="grid_form">
	<?php
	
	if($module == 'Client')
	{
		if($mode == 'edit')
		{
			$Cval 	= $clival;
			$timesheetObj->getAssignments($empnames, '', $servicedate, $servicedateto, '0',$module,'','',$Cval);
		}
		else
		{
			//get the client id of selected employee
			$Cval = $timesheetObj->clientcheckingArr[$empnames];
			$timesheetObj->getAssignments($empnames, '', $servicedate, $servicedateto, '0',$module,'','',$Cval);
		}
	}
	else
	{
		$timesheetObj->getAssignments($empnames, '', $servicedate, $servicedateto, '0');	
	}
	
	
	$rate_types_count = count($timesheetObj->getRateTypesForAllAsgnnames($timesheetObj->listOfAssignments,'','UOM'));
	$Tablewidth = ($rate_types_count * 11) + 35;
	if($Tablewidth < 100)
	$Tablewidth = 100;
	?>
	<table id="MainTable" cellspacing="0" cellPadding="2" style="width:100%;"  border=0 width="99%" align="center" class="ProfileNewUI CustomTimesheetTh CustomTimesheetInput TimeSheetContM"> 
		<?php
		$defaultHeaders = array('Date', 'Assignments');
		if(MANAGE_CLASSES == 'Y')
		{
			array_push($defaultHeaders, 'Class');
		}
		
		$f_totalhours = 0;
		
		if($mode == 'edit')
		{
			$Cval = '';
			if($module == 'Client') {
				$Cval 	= $clival;
			}
			$timesheetObj->getAssignments($empnames, '', $servicedate, $servicedateto, '0',$module,'','',$Cval);
			echo $timesheetObj->buildDynamicHeaders($defaultHeaders, $timesheetObj->listOfAssignments,'UOM');				
			$ratecountm=	$editrate = $timesheetObj->getRateTypesForAllAsgnnames($timesheetObj->listOfAssignments,'','UOM');	
			$rateids_arr =array();
			$ratetype_ids = $timesheetObj->getRateTypes();

			foreach($ratetype_ids as $val)
			{
				if(in_array($val['rateid'], $ratecountm))
				{
					array_push($rateids_arr, $val['rateid']);
				}
			}
			$cond ='';
			if($module == 'Client')
			{
				require("selfServicePref.php");
				

				
				$tsConditions 		= buildTimesheetConditions($userSelfServicePref[7]);
				$tsConditionsArr 	= explode("|",$tsConditions);
				
				$tsBillContactCon	= $tsConditionsArr[0];
				$conJoin 		= str_replace("hrcon_jobs.","hj.",$tsConditionsArr[1]);
				$condCk			= $tsConditionsArr[2];
				$conJoinBill		= $tsConditionsArr[3];
				
				$Cval = $timesheetObj->getClientId($username);
				if($Cval != $clival) // means billing contact related timesheets
				{
					$Cval 	= $clival;
					$condCk	= "";
					$conJoin= "";
				}				
				
				$condCk_comp=" and t2.client IN ($Cval) AND ";
				
				if(strpos($userSelfServicePref[7],"+6+"))
					$bill=" t2.billable !='' AND t2.billable !='No' AND ";
				
				$cond =$condCk_comp.$condCk.$bill."  t1.username = '" . $addr . "' AND t2.parid='".$sno."' and t2.client IN (".$Cval.")";

			}	
				
			
			echo "";
			$exportCond = '';
			if($_GET['frompage'] == 'exported' && $accountingExport == 'Exported')
			{
				$exportCond = 't2.exported_status = \'YES\' AND ';
				$ts_status = "Approved', 'Billed";
			}
			if($_GET['frompage'] == 'approved' && $accountingExport == 'Exported')
			{
				$exportCond = 't2.exported_status != \'YES\' AND ';
			}
			
			if ((isset($statusvalue) && $statusvalue == 'statapproved') && (isset($frompage) && $frompage == 'exported'))
			{
			
				$ts_status = 'Approved';
			}
			
			$sql = "SELECT SUM(t2.hours) totalHrs, t1.sdate servicedate, t1.edate servicedateto , t1.issues, t2.username, t2.sdate, t2.edate, t2.CLIENT, t2.task, GROUP_CONCAT(CONCAT(t2.hourstype, '|', t2.hours, '|', billable)) AS rtype, t2.assid, t2.classid , GROUP_CONCAT(t2.sno) tssno,GROUP_CONCAT(t2.hourstype) AS rate,GROUP_CONCAT(concat(t2.sno,'-',t2.hourstype)) AS snorate, t2.qbid, t1.astatus, t2.rowid,hj.mulrates FROM par_timesheet t1 INNER JOIN timesheet_hours t2 ON t1.sno = t2.parid 
			LEFT JOIN (select * from hrcon_jobs LEFT JOIN (SELECT asgnid,GROUP_CONCAT( CONCAT_WS( '^^', ratemasterid, period ) SEPARATOR '&&' ) AS mulrates 
			FROM `multiplerates_assignment` 
			WHERE ratetype = 'billrate' AND asgn_mode = 'hrcon' AND STATUS = 'Active' GROUP BY asgnid)
			multi_rates ON multi_rates.asgnid=hrcon_jobs.sno ) AS hj ON t2.assid = hj.pusername LEFT JOIN staffacc_cinfo sc ON t2.client = sc.sno INNER JOIN emp_list el ON el.username = t1.username
			LEFT JOIN users u ON u.username = t2.auser ".$conJoin." WHERE ".$exportCond." t1.sno = '".$sno."' AND t2.status in( '".$ts_status."' ) AND t2.payroll='' ".$cond." GROUP BY t2.rowid";
			//echo $sql;
			$result = mysql_query($sql, $db);
			$range = 'no';
			
			//print_r($rate_types_edit);
			while($row = mysql_fetch_assoc($result))
			{
				$rowCount 	= $row['rowid'];
				$tssno_str 	= $row['tssno'];
				$tssno_strrate 	= $row['snorate'];
				$tsrate_str 	= $row['rate'];
				$explode_rate 	= explode(',',$tsrate_str);
				$explode_sno 	= explode(',',$tssno_str);
				$explode_snorate = explode(',',$tssno_strrate);
				
				foreach($editrate  as $key => $valu)
				{
					if(in_array($valu,$explode_rate))
					{
						foreach($explode_snorate as $rateval)
						{
							$abcarr = explode('-',$rateval);
							//print_r($abcarr);
							if($abcarr[1] == $valu)
							{
								$edit_string .= "|".$row['astatus']."|".$abcarr[0]."||".$row['classid']."|".$row['qbid']."#^#";
								$edit_string_1 .= "|".$row['astatus']."|".$abcarr[0]."||".$row['classid']."|".$row['qbid']."#^#";
							}
						}
					}
					else
					{
						$edit_string .= "|||||#^#";
						$edit_string_1 .= "|||||#^#";
					}
				}

				$sdate 			= date('m/d/Y', strtotime($row['sdate']));
				if($row['edate'] != '0000-00-00')
				{
					$edate = date('m/d/Y', strtotime($row['edate']));
					$range = 'yes';
					$dates_array = $timesheetObj->buildDatesdropdown($timesheet_date_arr, $servicedate, $servicedateto,false,'yes');
				}
				else
				{
					$edate = $sdate;
					$dates_array = $timesheetObj->buildDatesdropdown($timesheet_date_arr, $servicedate, $servicedateto,false,'no');
				}

			
				// To handle a use case where there are multiple values for single ratetype and rowid is also same
				$row['rtype'] = $timesheetObj->getDetailsBySnos($tssno_str);
			
				if($module == 'Client')
				{
					$Cval = $timesheetObj->clientcheckingArr[$row['username']]; // Client values over writing to load all assignment in the drop down
				}
				echo $timesheetObj->getRangeRow($row['username'], $row['assid'], $row['rtype'], $row['task'], $dates_array, $sdate, $edate, $row['classid'], $rowCount, $range, $row['tssno'], $edit_string, $row['rowid'],$module,$row['totalHrs'], $Cval,'UOM');
				//$rowCount++;
				 $edit_string_1.="^^";
				 
				$f_totalhours = $f_totalhours+$row['totalHrs'];
			}
			//echo "total hours are ".$f_totalhours."<br>";
			$allsnos = implode(',', $timesheetObj->mystr);
			
			$maxRowId = $timesheetObj->getMaxRowId($sno);
			$rowCount = $maxRowId;
			
			$rowCount++;
		}
		else
		{
			if($asgnflag)
			{
				//$TSEnteredData = $timesheetObj->getTSFilledData($empnames,  $servicedate, $servicedateto);
				$Cval = '';
				if($module == 'Client') {
					$Cval = $timesheetObj->clientcheckingArr[$empnames];
				}
				
				$timesheetObj->getAssignments($empnames, '', $servicedate, $servicedateto, '0',$module,'','',$Cval);
				
				
				echo $timesheetObj->buildDynamicHeaders($defaultHeaders, $timesheetObj->listOfAssignments,'UOM');
				$ratecountm=   $timesheetObj->getRateTypesForAllAsgnnames($timesheetObj->listOfAssignments,'','UOM');	
				$rateids_arr =array();
				$ratetype_ids = $timesheetObj->getRateTypes();

				foreach($ratetype_ids as $val)
				{
					if(in_array($val['rateid'], $ratecountm))
					{
						array_push($rateids_arr, $val['rateid']);
					}
				}
				$rowCount = 1;
				$enteredData = '';
				
				//Range Row
				$dates_array = $timesheetObj->buildDatesdropdown($timesheet_date_arr, $servicedate, $servicedateto,false,'yes');
				echo $timesheetObj->getRangeRow($empnames, $asgnid, '', '', $dates_array, $servicedate, $servicedateto, $classid, 0,'yes','','','',$module,'',$Cval,'UOM');
				
				// Days rows
				$dates_array = $timesheetObj->buildDatesdropdown($timesheet_date_arr, $servicedate, $servicedateto,false,'no');
				
				foreach($timesheet_date_arr as $key=>$val)
				{
					
					$timesheet_start_date_day = explode(" ", $val);
					
					if($timesheetObj->checkAssignmentExists($empnames, $timesheet_start_date_day[0], $timesheet_start_date_day[0], $module, $Cval))	
					{
						echo $timesheetObj->getRangeRow($empnames, $asgnid, '', '', $dates_array, $timesheet_start_date_day[0], $timesheet_start_date_day[0], $classid, $rowCount,'','','','',$module,'',$Cval,'UOM',$rateids_arr);
						$rowCount++;
					}
					if($rowCount > 7)
					{
						break;
					}
				}
			}
			else
			{
				?><tr><td colspan='3' align='center'>No Assignments Found<br /></td></tr>
			<?php }
			
		}
		$colspan=2;
		if(MANAGE_CLASSES == 'Y'){
			$colspan=3;
		}
		if($asgnflag)
		{
		?>
			<tr>
				<td class="totbg">&nbsp;</td>
				<?php if($module=='Client'){?>
					<td class="totbg">&nbsp;</td>
				<?php } ?>
				<td colspan=<?php echo $colspan; ?> align=right class="totbg"><div class="hrsDiv"><b>Total Hours : </b></div><div class="dayDiv"><b>Total Days : </b></div><div class="mileDiv"><b>Total Miles : </b></div><div  class="unitDiv"><b>Total Units : </b></div></td>
				<?php echo $timesheetObj->buildCheckBox_TotalHours('timesheetRate', $timesheetObj->listOfAssignments,'UOM'); ?>
				<td class="totbg">
					<!--<input type="hidden" name="f_totalhrs" id="f_totalhrs" value="0.00" >-->
					<input type="hidden" name="f_totalhrs" id="f_totalhrs" value="<?php echo $f_totalhours;?>" >
					<input type="hidden" name="f_totaldays" id="f_totaldays" value="<?php echo $f_totalhours;?>" >
					<input type="hidden" name="f_totalmiles" id="f_totalmiles" value="<?php echo $f_totalhours;?>" >
					<input type="hidden" name="f_totalunits" id="f_totalunits" value="<?php echo $f_totalhours;?>" >
					<input type='hidden' id='allsnos' name='allsnos' value='<?php echo $allsnos;?>'>
					<input type='hidden' id='edit_string_new' name='edit_string_new' value='<?php echo $edit_string; ?>'>
                    <input type='hidden' id='edit_string_new_1' name='edit_string_new_1' value='<?php echo $edit_string_1; ?>'>
					<input type='hidden' id='edit_sno_ids' name='edit_sno_ids' value=''>
					<input type='hidden' id='edit_sno_ids_final' name='edit_sno_ids_final' value=''>
					<input type='hidden' id='delete_sno_ids' name='delete_sno_ids' value=''>
					<input type=hidden name=statval value="<?php echo $_GET['statusvalue'];?>">
					<input type="hidden" name="dynrowcount" id="dynrowcount" value="<?php echo $rowCount;?>" >
				<!--	<input type="hidden" name="colcount" id="colcount" value="<?php echo count($timesheetObj->assignments);?>" > -->
				<input type="hidden" name="colcount" id="colcount" value="<?php echo count($ratecountm);?>" >
				<input type="hidden" name="frompage" id="frompage" value="<?php echo $frompage;?>" >
					<div id="final_totalhrs" class="hrsDiv totsDiv" style="width: 50px">0.00</div>
					<div id="final_totaldays" class="dayDiv totsDiv" style="width: 50px">0.00</div>
					<div id="final_totalmiles" class="mileDiv totsDiv" style="width: 50px">0.00</div>
					<div id="final_totalunits" class="unitDiv totsDiv" style="width: 50px">0.00</div>
				</td>
				
				
			</tr>
			<tr class=CustomGrandTotal>
				<td class="totbg">&nbsp;</td>
				<td class="totbg" colspan=<?php echo $colspan; ?> align=right class"><div><b>Grand Total : </b></div></td>
				<?php echo $timesheetObj->build_TotalHours($timesheetObj->listOfAssignments,'UOM'); ?>
				<td class="totbg"><div id="final_total" class="totsDiv" style="width: 50px">0.00</div></td>
			</tr>
		<?php
		}
		?>
		</table>
	</div>
</td>
</div>
</table>
<input type="hidden" name="hiddenBillable" id="hiddenBillable" value="<?php echo implode(",", $timesheetObj->hiddenBillable[0]) ; ?>">
<?php
if($asgnflag)
{
?>
<div class="timeRemarks">
<table width=99% cellpadding=3 cellspacing=0 border=0>
	<?php
	if ($module == 'Accounting') {

		if ($mode == 'edit') {

			if (!empty($issues)) {
	?>
				<tr>
					<td valign="top"><font class="afontstylee">Remarks</font></td>
					<td class="afontstylee"><?php echo stripslashes($issues);?></td>
					<input type="hidden" name="issues" id="issues" value="<?php echo $issues ; ?>">
				</tr>
	<?php
			}
	?>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td valign="top"><font class="afontstylee">Notes</font></td>
			</tr>
            <tr>
				<td>
					<textarea cols="127" rows="3" wrap="virtual" name="notes" tabindex="<?php echo $tab_index=$tab_index+1;?>"><?php echo html_tls_specialchars(stripslashes($notes),ENT_QUOTES);?></textarea>
				</td>
			</tr>
	<?php
		} else {
	?>
			<tr>
				<td valign="top"><font class="afontstylee">Remarks</font></td>
			</tr>
            <tr>
				<td>
					<textarea cols="127" rows="3" wrap="virtual" name="issues" tabindex="<?php echo $tab_index=$tab_index+1;?>"></textarea>
				</td>
			</tr>
	<?php
		}

	} elseif ($module == 'MyProfile') {
	?>
		<tr>
			<td valign=top><font class="afontstylee">Remarks</font></td>
		</tr>
        <tr>
			<td>
				<textarea cols="127" rows="3" wrap="virtual" name="issues" tabindex="<?php echo $tab_index=$tab_index+1;?>"><?php echo stripslashes($issues);?></textarea>
			</td>
		</tr>
	<?php
	} elseif ($module == 'Client') {

		if ($mode == 'edit') {

			if (!empty($issues)) {
	?>
				<tr>
					<td valign="top"><font class="afontstylee">Remarks</font></td>
				</tr>
            	<tr>
					<td class="afontstylee"><?php echo stripslashes($issues);?></td>
					<input type="hidden" name="issues" id="issues" value="<?php echo $issues; ?>">
				</tr>
	<?php
			}
	?>
			<tr>
				<td valign="top"><font class="afontstylee">Notes</font></td>
			</tr>
            <tr>
				<td>
					<textarea cols="127" rows="3" wrap="virtual" name="notes" tabindex="<?php echo $tab_index=$tab_index+1;?>"><?php echo html_tls_specialchars(stripslashes($notes),ENT_QUOTES);?></textarea>
				</td>
			</tr>
	<?php
		} else {
	?>
		<tr>
			<td valign="top"><font class="afontstylee">Remarks</font></td>
		</tr>
        <tr>
			<td>
				<textarea cols="127" rows="3" wrap="virtual" name="issues" tabindex="<?php echo $tab_index=$tab_index+1;?>"></textarea>
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
	if($mode == 'edit')
	{
	?>
	<tr>
		<td colspan=2>
			<?php
			echo $timesheetObj->getTimesheetAttachments($sno, 'edit');
			?>
		</td>
	</tr>
	<?php
	}
	?>
	<tr>
		<td colspan='2'><font class=afontstylee style=margin-right:35px; >&nbsp;Upload Timesheet File</font> <input type=file name=timefile id=timefile size=65 tabindex='<?php echo $tab_index=$tab_index+1;?>'/></td>
		<td></td>
	</tr>
</table>
</div>
<?php
}
?>
	<table width=99% cellpadding=0 cellspacing=0 border=0  align="center">
<div id="topheader" >
	<tr class="NewGridBotBg">
	<?php
	//$menu->showHeadingStrip1($name,$link,$heading);
	?>
	</tr>

	</div></table>
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
</form>
<?php
/* DONT DELETE - Contains Disclaimer Content to be shown in Lightbox */
?>
<div id="disclaimer_content" style="display: none;"><?php echo $dis_message; ?></div>

<script language="javascript" type="text/javascript">
function AddTaskDetails(rownumberval)
{	
     var rownumberarray= rownumberval.split("_");
	 var rownumber = rownumberarray[1];
	$("#np_"+rownumber).show();
	$("#np_"+rownumber).focus();
	$("#textlabel_"+rownumber).hide();
}

function AddTextHideBox(rownumber)
{	
	alert("this the row where I am editing - - - "+rownumber);
	
}

function chainNavigation() {	
	
	//Arrow UP/Down Code
	$("#MainTable tr.tr_clone input[type=text][max_length='5']" ).each( function( i, el ) {
		//alert($(el).attr('class'));
		var iclass = $(el).attr('class');
		var class_arr = iclass.split(" ");
	    	var $box_Regular = $(':input.'+class_arr[0]);
		$box_Regular.each(function(i) {
			$(this).data('next', $box_Regular[i + 1]);
			$(this).data('prev', $box_Regular[i - 1]);
		});
	});	
}
function gettitles(title){
	if(title == 'Day' || title == 'Miles' || title == 'Units'){
		return 0;
	}else{
		return 1;
	}
}
function TimesheetCalc(keyrow,rowval,total,rowfocus)
{
	var tempTotal= 0.00;
	var tempTotaldays= 0.00;
	var tempTotalmiles= 0.00;
	var tempTotalunits= 0.00;
	try{
		var rowarr= rowval.split("_");
		var row = rowarr[3];
	}
	catch(e)
	{
		var row=rowval;
	}
	//DayWeek Tab Functionality
	var dayWeekTrClass = getSelectedTab();
	
	$("#MainTable "+dayWeekTrClass+" input."+keyrow+"").each(function() {
			var hrs_name = $(this).attr("name");
			var hrs_id = $(this).attr("id");			
			var hrs_val = $(this).val();
			if(hrs_val!='')
			{				
				var frm1 = document.getElementById(hrs_id);
				var getAttr = frm1.getAttribute('rate_uom');
				var gettitle = gettitles(getAttr);
				if(isHoursNew(frm1,getAttr))
				{
					if(gettitle == 1){
						tempTotal = parseFloat(tempTotal) + parseFloat(hrs_val) ;	
						
					}else if(gettitle == 0){
						if(getAttr == 'Day'){
							tempTotaldays = parseFloat(tempTotaldays) + parseFloat(hrs_val) ;	
						}else if(getAttr == 'Miles'){
							tempTotalmiles = parseFloat(tempTotalmiles) + parseFloat(hrs_val) ;	
						}else if(getAttr == 'Units'){
							tempTotalunits = parseFloat(tempTotalunits) + parseFloat(hrs_val) ;	
						}
					}
					flag=true;	
				}else
				{
				  frm1.value='';
				  flag=false;
				  return false;
				}
			}
		});
		
		$("#MainTable input[name="+keyrow+"_input]").val(NumberFormatted(tempTotal));
		$("#MainTable #"+keyrow+"_div").html(NumberFormatted(tempTotal));
		$("#MainTable input[name="+keyrow+"_day_input]").val(NumberFormatted(tempTotaldays));
		$("#MainTable #"+keyrow+"_day_div").html(NumberFormatted(tempTotaldays));
		$("#MainTable input[name="+keyrow+"_mile_input]").val(NumberFormatted(tempTotalmiles));
		$("#MainTable #"+keyrow+"_mile_div").html(NumberFormatted(tempTotalmiles));
		$("#MainTable input[name="+keyrow+"_unit_input]").val(NumberFormatted(tempTotalunits));
		$("#MainTable #"+keyrow+"_unit_div").html(NumberFormatted(tempTotalunits));
		TotalDay_TimesheetCalc(row,total,rowfocus);
		Final_TimesheetCalc(total);
}

function checkObject(obj){
	return obj && obj !== "null" && obj !== "undefined";
}

function Final_TimesheetCalc(total)
{

	var ftot = 0.00;var totdays = 0.00;var totmiles = 0.00;var totunits = 0.00;
	each_rowVal = 0.00;
	for (var i=0;i<total;i++)
	{ 
	
		if(checkObject($("#MainTable input[name=timesheetRate"+i+"_input]").val())) {
			var each_rowVal = $("#MainTable input[name=timesheetRate"+i+"_input]").val();
			ftot =  parseFloat(ftot) + parseFloat(each_rowVal);
		}
		if(checkObject($("#MainTable input[name=timesheetRate"+i+"_day_input]").val())) {
			var each_rowVal = $("#MainTable input[name=timesheetRate"+i+"_day_input]").val();
			totdays =  parseFloat(totdays) + parseFloat(each_rowVal);
		}
		if(checkObject($("#MainTable input[name=timesheetRate"+i+"_mile_input]").val())) {
			var each_rowVal = $("#MainTable input[name=timesheetRate"+i+"_mile_input]").val();
			totmiles =  parseFloat(totmiles) + parseFloat(each_rowVal);
		}
		if(checkObject($("#MainTable input[name=timesheetRate"+i+"_unit_input]").val())) {
			var each_rowVal = $("#MainTable input[name=timesheetRate"+i+"_unit_input]").val();
			totunits =  parseFloat(totunits) + parseFloat(each_rowVal);
		}		
	}
	$("#f_totalhrs").val(NumberFormatted(ftot));
	$("#final_totalhrs").html(NumberFormatted(ftot)); 
	
	$("#f_totaldays").val(NumberFormatted(totdays));
	$("#final_totaldays").html(NumberFormatted(totdays)); 
	
	$("#f_totalmiles").val(NumberFormatted(totmiles));
	$("#final_totalmiles").html(NumberFormatted(totmiles)); 
	
	$("#f_totalunits").val(NumberFormatted(totunits));
	$("#final_totalunits").html(NumberFormatted(totunits)); 
	
	grand_total();
	
}

function TotalDay_TimesheetCalc(row,total,rowfocus)
{

	var dtot = 0.00;
	var days_tot = 0.00;
	var miles_tot = 0.00;
	var units_tot = 0.00;
	for (var i=0;i<total;i++)
	{ 
		if(checkObject($("#MainTable input[id=daily_rate_"+i+"_"+row+"]").val())){
			var eachDayrowVal = $("#MainTable input[id=daily_rate_"+i+"_"+row+"]").val();	
			if(eachDayrowVal!=''){
				var rate_title = $("#MainTable input[id=daily_rate_"+i+"_"+row+"]").attr('rate_uom');
				if(rate_title == 'Day'){
					days_tot =  parseFloat(days_tot) + parseFloat(eachDayrowVal);
				}
				else if(rate_title == 'Miles'){
					miles_tot =  parseFloat(miles_tot) + parseFloat(eachDayrowVal);
				}
				else if(rate_title == 'Units'){
					units_tot =  parseFloat(units_tot) + parseFloat(eachDayrowVal);
				}
				else{
					dtot =  parseFloat(dtot) + parseFloat(eachDayrowVal);
				}
				
			}
		}	
	}
	$("#daytotalhrs_"+row).val(NumberFormatted(dtot));
	$("#daytotalhrsDiv_"+row).val(NumberFormatted(dtot));
	$("#daytotalhrs_"+row).val(NumberFormatted(dtot));
	
	$("#daystotalDiv_"+row).val(NumberFormatted(days_tot));
	$("#totaluomdays_"+row).val(NumberFormatted(days_tot));
	
	$("#milestotalDiv_"+row).val(NumberFormatted(miles_tot));
	$("#totaluommiles_"+row).val(NumberFormatted(miles_tot));
	
	$("#unitstotalDiv_"+row).val(NumberFormatted(units_tot));
	$("#totaluomunits_"+row).val(NumberFormatted(units_tot));
}
function grand_total(){
	var hours_total = $("#MainTable #final_totalhrs").html();	
	var days_total = $("#MainTable #final_totaldays").html();	
	var miles_total = $("#MainTable #final_totalmiles").html();	
	var units_total = $("#MainTable #final_totalunits").html();	
	 
	var grandTotal = parseFloat(hours_total)+parseFloat(days_total)+parseFloat(miles_total)+parseFloat(units_total);
	$("#MainTable #final_total").html(NumberFormatted(grandTotal));
}
$("div").click(function(){

  $("#div1").css("background-color","#C5EAF6");

  });

function delTimeAttach(sno, parid)
{
	var url = "delete_timefile.php?sno="+sno+"&parid="+parid;
	$.get(url, function( data ) {
		$("#"+sno).remove();	
	});

}

function printObject(o) {
  var out = '';
  for (var p in o) {
    out += p + ': ' + o[p] + '\n';
  }
  alert(out);
}

function unique(nav_array) {
    nav_array = nav_array.sort(function (a, b) { return a*1 - b*1; });      
    var ret = [nav_array[0]];       
    // Start loop at 1 as element 0 can never be a duplicate
    for (var i = 1; i < nav_array.length; i++) { 
        if (nav_array[i-1] !== nav_array[i]) {              
            ret.push(nav_array[i]);             
        }       
    }
    return ret;     
}

function checkEditsnos(rateid){
	var oldval = $(rateid).attr("value");
	$(rateid).bind("change paste keyup", function() {
		var dInput = this.value;
		if(NumberFormatted(oldval) != NumberFormatted(dInput)){
			var edited_snos_str = rateid.split("_");
			var edit_snos = $("input[type=hidden][name='edit_snos_new["+edited_snos_str[3]+"]']").val();
			var edit_sno_old  = $('#edit_sno_ids').attr("value");
			$('input:hidden[name="edit_sno_ids"]').val(edit_sno_old+"#^#"+edit_snos);
		}
	});
}
function getUOMTimesheetDateRangeBeforeSubmit(){

	var moduleName = document.getElementById("module").value;	
	
	if(moduleName == 'MyProfile'){
		var empUsernames = document.getElementById("empnames_myprofile").value;
	}else{
		var emp = document.getElementById("empnames");
		var empUsernames = emp.options[emp.selectedIndex].value;
	}
	

	var checking_from = document.getElementById("checking_from").value;
	var checking_to = document.getElementById("checking_to").value;
	var getdates = document.getElementById("getdates").value;
	var lockdownflag = document.getElementById("lockdown_flag").value;
	
	var content = "rtype=getTimesheetStatus&multiple=NO&moduleName="+moduleName+"&dateRangeFilled=No&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames+"&getdates="+getdates+"&onloadCheck=onload"+"&lockdown_flag="+lockdownflag;
	$.ajax({
	    url: '/BSOS/Include/getAsgn.php',
	    data: content,
	    method: 'POST',
	    success: function (response)
	    {	
	    	if(response !=0){
	    		var dateRange = response.split('|');
	    		alert("Timesheet for the below dates already exists.\n"+dateRange[0]+"\nClick OK to go back and change the dates and submit time sheet for different dates.");
	    		$('#tcalico_0').click();
	    	}
		 
	    }
	});
}

$(document).ready(function(){
	buildDayWeekTabs();
	
	//function to bind the select2 for employee list on timesheets.
	
	if(module != "MyProfile" && mode != "edit" && module!= 'Accounting') {
		$("#empnames").select2();
	}
	var module = '<?php echo $module;?>';
	var mode = '<?php echo $mode;?>';
	if(module == "Accounting") {
			var assignSdate = '<?php echo $servicedate;?>';
			var assignEdate = '<?php echo $servicedateto;?>';
			var pageModule = '<?php echo $module;?>';
			$("#empnames").select2({
	    
		        //placeholder: "Select an Employee",
		        minimumInputLength: 0,
		        closeOnSelect: true,
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
	
	var ess_user = document.getElementById("ess_user").value;

	//for displaying alert message if any timesheet is submit based on the selected date range. - only for ess user
	var lockdownflag = document.getElementById("lockdown_flag").value;
	if(module == "MyProfile" && ess_user=="YES" && mode=="" && lockdownflag=="dont_allow_duplicate"){
	    getUOMTimesheetDateRangeBeforeSubmit();
	}

	if(module != 'MyProfile' && mode != 'edit')
	{
		var empacc = $(".employees option:selected").val();
	}
	else if(module == 'MyProfile' && mode != 'edit')
	{
		var empacc = $("#empnames_myprofile").val();
	}
	else if(module != 'MyProfile' && mode == 'edit')
	{
		var empacc = $("#empnames_myprofile").val();
	}
	else if(module == 'MyProfile' && mode == 'edit')
	{
		var empacc = $("#empnames_myprofile").val();
	}
	
	var mode =  '<?php echo $edit_mode; ?>';
	if (mode=='edit')
	{

		var id = $('tr:nth-child(3)').attr('id');
		var first_id = id.replace("row_", "");
		var rates_length = "<?php echo $rate_types_count; ?>";
		var row_count =  <?php echo $rowCount; ?>;
		for (var i=first_id; i<=row_count;i++)
		{
			get_cur_placeholders("daily_assignemnt_"+i,i);
		}

		  
		$('#MainTable').hover(function(event) {
			var id = $('tr:nth-child(3)').attr('id');
			var start_id = id.replace("row_", "");
			var row_count =  <?php echo $rowCount; ?>;
			var rates_length = "<?php echo $rate_types_count; ?>";
			//var edit_snos = new Array();
			for (var i=start_id; i<row_count;i++)
			{
				for(var j=0; j<rates_length; j++){
					var rateid = '#daily_rate_'+j+'_'+i;
					checkEditsnos(rateid);
				}
			}
			var edit_snos_final  = $('#edit_sno_ids').attr("value");
			var edit_snos_array = new Array();
			edit_snos_array = edit_snos_final.split('#^#');
			var final_edited = unique(edit_snos_array);
			var edit_ids = final_edited.join("#"); 
			$('input:hidden[name="edit_sno_ids_final"]').val(edit_ids);
		});
	}
	
	$(".daily_dates").change(function(){
	
		var eleid = this.id;
		var daterowid = this.id.split("_").pop(-1);
		var dateval = $("#"+this.id).val();
		
		/////////////////////////////////
		
		
		var gettotal;
		var oldCellVal;
		
		calc_totalhours_uom(this.id);
		
		/////////////////////////////////
		var client_id	= 0;
		if (module == "Client") {

			client_id	= "<?php echo $Cval; ?>";
		}

		var url = "loadtimedata.php?date="+dateval+"&empacc="+empacc+"&rowid="+daterowid+"&mod=dates&module="+module+"&client_id="+client_id+"&ts_type=UOM";
		$.get(url, function( data ) {
			if (!data)
			{
				alert("There are no assignments available on this date to create Timesheet");
				return;
			}
			else
			{
				$("#span_"+daterowid).html(data);
				
				//binding select2 after pushing the new html content
				var customSelectElement = $('#MainTable #span_'+daterowid+' select');
				bindSelect2(customSelectElement);
				
				$("#daily_assignemnt_"+daterowid).on('change', function(){
					calc_totalhours_uom(this.id);
					getRateTypes(this.id);
					
				});
				getRateTypes("daily_assignemnt_"+daterowid);	
				
			
			}
			
		});
		
		
	});
	
	$(".daily_assignemnt").change(function(){
	
		var gettotal;
		var oldCellVal;
				
		calc_totalhours_uom(this.id);
				
		getRateTypes(this.id);
		//On changing the assignment the place holders should be there with rate type START
	
		///On changing the assignment the place holders should be there with rate type END
	});
	//For getting the HOUR ->Hours placeholders
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
				
			/*
			case 'HOUR':
				asgnratetype = 'Hours';
				break;
			case 'DAY':
				asgnratetype = 'Days';
				break;
			case 'WEEK':
				asgnratetype = 'Weeks';
				break;
			case 'MONTH':
				asgnratetype = 'Months';
				break;
			case 'YEAR':
				asgnratetype = 'Years';
				break;
			case 'FLATFEE':
				asgnratetype = 'Flat Fee';
				break;*/
		}
		return asgnratetype;
	}
	function get_cur_placeholders(id_val,cindex){
		//var id_val = '';//daily_assignemnt_"+cindex+//this.id//daily_assignemnt_"+daterowid+//daily_assignemnt_"+u+"
		//var cindex = '';
		$("#row_"+cindex+" .rates").attr("placeholder", "");
		$("#row_"+cindex+" .rates").attr("title", "");
		$("#row_"+cindex+" .rates").attr("rate_uom", "");
		if(typeof $("#"+id_val+" option:selected").attr('class') != 'undefined' && $("#"+id_val+" option:selected").attr('class') != 'false'){
				var asgn_rates = $("#"+id_val+" option:selected").attr('class').split("&&");
				
				for(var p = 0; p < asgn_rates.length; p++){
					asgnrate=asgn_rates[p].split("^^");
					var asgnratetype = rate_type_con(asgnrate[1]);
					if($("#row_"+cindex+" ."+asgnrate[0]).attr('disabled') !='disabled'){
						$("#row_"+cindex+" ."+asgnrate[0]).attr("placeholder", asgnratetype);
						$("#row_"+cindex+" ."+asgnrate[0]).attr("title", asgnratetype);
						$("#row_"+cindex+" ."+asgnrate[0]).attr("rate_uom", asgnratetype);
					}
				}
				
			}else{
				$("#row_"+cindex+" .rate1").attr("placeholder", "Hours");$("#row_"+cindex+" .rate2").attr("placeholder", "Hours");$("#row_"+cindex+" .rate3").attr("placeholder", "Hours");
				$("#row_"+cindex+" .rate1").attr("title", "Hours");$("#row_"+cindex+" .rate2").attr("title", "Hours");$("#row_"+cindex+" .rate3").attr("title", "Hours");
				$("#row_"+cindex+" .rate1").attr("rate_uom", "Hours");$("#row_"+cindex+" .rate2").attr("rate_uom", "Hours");$("#row_"+cindex+" .rate3").attr("rate_uom", "Hours");
				
			}
			get_totalsDiv_asgnmt();
			
	}
	
	function calc_totalhours_uom(id_total){
	
		var cellval;
		var colsum;
		var colcountvalue = $("#colcount").val();
		
		var splitid = id_total.split('_');
		var rowid = splitid.pop();
		for(var count=0; count < colcountvalue; count++)
		{
			if ($("#daily_rate_"+count+"_"+rowid).val() != '')
			{
				cellval = $("#daily_rate_"+count+"_"+rowid).val();
			}
			else
			{
				cellval = 0;
			}
			
			var rate_title = $("#daily_rate_"+count+"_"+rowid).attr('rate_uom');
			
			if(rate_title == 'Day'){
				if ($("#timesheetRate"+count+"_day_div").text() != ''){
					colsum = $("#timesheetRate"+count+"_day_div").text();
				}else{
					colsum = 0;
				}
			}
			else if(rate_title == 'Miles'){
				if ($("#timesheetRate"+count+"_mile_div").text() != ''){
					colsum = $("#timesheetRate"+count+"_mile_div").text();
				}else{
					colsum = 0;
				}
			}
			else if(rate_title == 'Units'){
				if ($("#timesheetRate"+count+"_unit_div").text() != ''){
					colsum = $("#timesheetRate"+count+"_unit_div").text();
				}else{
					colsum = 0;
				}
			}
			else{
				if ($("#timesheetRate"+count+"_div").text() != ''){
					colsum = $("#timesheetRate"+count+"_div").text();
				}else{
					colsum = 0;
				}
			}
			var diff =NumberFormatted( Math.abs(parseFloat(colsum)-parseFloat(cellval)));
			
			if(rate_title == 'Day'){
				$("#timesheetRate"+count+"_day_div").text(diff);
				$("#MainTable input[name=timesheetRate"+count+"_day_input]").val(NumberFormatted(diff));
			}
			else if(rate_title == 'Miles'){
				$("#timesheetRate"+count+"_mile_div").text(diff);
				$("#MainTable input[name=timesheetRate"+count+"_mile_input]").val(NumberFormatted(diff));
			}
			else if(rate_title == 'Units'){
				$("#timesheetRate"+count+"_unit_div").text(diff);
				$("#MainTable input[name=timesheetRate"+count+"_unit_input]").val(NumberFormatted(diff));
			}
			else{
				$("#timesheetRate"+count+"_div").text(diff);
				$("#MainTable input[name=timesheetRate"+count+"_input]").val(NumberFormatted(diff));
			}
			
		}
	}
	function getRateTypes(asgnid)
	{
		var asgnrowid = asgnid.split("_").pop(-1);
		var asgnval = $("#"+asgnid).children(":selected").attr("id");
		var asgnvaltext = $("#"+asgnid).children(":selected").text();
		var asgnid1 = asgnval.split("-");
		var url = "loadtimedata.php?empacc="+empacc+"&asgn="+asgnid1[0]+"&rowid="+asgnrowid+"&mod=asgn&ts_type=UOM";
		$.get(url, function( data ) {
			var dataArr = data.split("|");
			var ratecount = dataArr.length;
			
			for(i=0; i<ratecount-1; i++)
			{
				var dataArrinternal = dataArr[i].split(",");
				//Form the Custome rate field name and billable checkbox name based on the rate id
				var CRFieldIdObj = "daily_rate_"+asgnrowid+"["+asgnrowid+"]["+dataArrinternal[8]+"]";
				var CRBillableFieldIdObj = "daily_rate_billable_"+asgnrowid+"["+dataArrinternal[8]+"]";
				
				if(dataArrinternal[1] == 'Y')
				{				
					/* $("#MainTable input[id="+dataArrinternal[0]+"]").attr('disabled', false);
					$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', false);
					$("#MainTable input[id="+dataArrinternal[0]+"]").val('');  */
					
					$("#MainTable input[name=\""+CRFieldIdObj+"\"]").attr('disabled', false);
					$("#MainTable input[name=\""+CRBillableFieldIdObj+"\"]").attr('disabled', false);
					$("#MainTable input[name=\""+CRFieldIdObj+"\"]").val('');
				}
				else
				{
					/* $("#MainTable input[id="+dataArrinternal[0]+"]").attr('disabled', true);
					$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', false);
					$("#MainTable input[id="+dataArrinternal[2]+"]").attr('checked', false);
					$("#MainTable input[id="+dataArrinternal[0]+"]").val(''); */
					
					$("#MainTable input[name=\""+CRFieldIdObj+"\"]").attr('disabled', true);
					$("#MainTable input[name=\""+CRBillableFieldIdObj+"\"]").attr('disabled', false);
					$("#MainTable input[name=\""+CRBillableFieldIdObj+"\"]").attr('checked', false);
					$("#MainTable input[name=\""+CRFieldIdObj+"\"]").val('');
				}
				if(dataArrinternal[3] == 'Y')
				{
					/* $("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', false);
					$("#MainTable input[id="+dataArrinternal[2]+"]").attr('checked', true);
					$("#MainTable input[id="+dataArrinternal[0]+"]").val(''); */
				
					$("#MainTable input[name=\""+CRBillableFieldIdObj+"\"]").attr('disabled', false);
					$("#MainTable input[name=\""+CRBillableFieldIdObj+"\"]").attr('checked', true);
					$("#MainTable input[name=\""+CRFieldIdObj+"\"]").val('');
				}
				else
				{
					/* $("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', false);
					$("#MainTable input[id="+dataArrinternal[2]+"]").attr('checked', false);
					$("#MainTable input[id="+dataArrinternal[0]+"]").val(''); */
					
					$("#MainTable input[name=\""+CRBillableFieldIdObj+"\"]").attr('disabled', false);
					$("#MainTable input[name=\""+CRBillableFieldIdObj+"\"]").attr('checked', false);
					$("#MainTable input[name=\""+CRFieldIdObj+"\"]").val('');
				}


				var pay = (dataArrinternal[5]=='')?'0.00':dataArrinternal[5];
				var bill = (dataArrinternal[7]=='')?'0.00':dataArrinternal[7];
				$("#MainTable span[id="+dataArrinternal[4]+"]").html(pay);
				$("#MainTable span[id="+dataArrinternal[6]+"]").html(bill);
			}
			
			var dayhrs = $("#daytotalhrsDiv_"+asgnrowid).val();
			if (dayhrs == '') {
				dayhrs = 0.00;
			}
			var totalhrs = $("#final_totalhrs").text();
			var finalhrs = parseFloat(totalhrs)-parseFloat(dayhrs);
			if(finalhrs < 0) {
				$("#final_totalhrs").html("0.00");
				$("#f_totalhrs").val("0.00");
			}
			else
			{
				$("#final_totalhrs").html(finalhrs.toFixed(2));
				$("#f_totalhrs").val(finalhrs.toFixed(2));
			}
			
			calc_finaluom(asgnrowid);
			
			$("#daytotalhrsDiv_"+asgnrowid).val('0.00');
			$("#daytotalhrs_"+asgnrowid).val('0.00');
			$("#daystotalDiv_"+asgnrowid).val('0.00');
			$("#totaluomdays_"+asgnrowid).val('0.00');
			$("#milestotalDiv_"+asgnrowid).val('0.00');
			$("#totaluommiles_"+asgnrowid).val('0.00');
			$("#unitstotalDiv_"+asgnrowid).val('0.00');
			$("#totaluomunits_"+asgnrowid).val('0.00');
			
			grand_total();
			get_cur_placeholders(asgnid,asgnrowid);
		});
		
	}
	function calc_finaluom(asgnrowid){
		//For UOM DAYS
		var uom_dayhrs = $("#daystotalDiv_"+asgnrowid).val();
		if (uom_dayhrs == '') {
			uom_dayhrs = 0.00;
		}
		var total_uomhrs = $("#final_totaldays").text();
		var final_uomhrs = parseFloat(total_uomhrs)-parseFloat(uom_dayhrs);
		if(final_uomhrs < 0) {
			$("#final_totaldays").html("0.00");
			$("#f_totaldays").val("0.00");
		}
		else
		{
			$("#final_totaldays").html(final_uomhrs.toFixed(2));
			$("#f_totaldays").val(final_uomhrs.toFixed(2));
		}
		//For UOM Miles
		var uom_daymiles = $("#milestotalDiv_"+asgnrowid).val();
		if (uom_daymiles == '') {
			uom_daymiles = 0.00;
		}
		var total_uommiles = $("#final_totalmiles").text();
		var final_uommiles = parseFloat(total_uommiles)-parseFloat(uom_daymiles);
		if(final_uommiles < 0) {
			$("#final_totalmiles").html("0.00");
			$("#f_totalmiles").val("0.00");
		}
		else
		{
			$("#final_totalmiles").html(final_uommiles.toFixed(2));
			$("#f_totalmiles").val(final_uommiles.toFixed(2));
		}
		//For UOM Units
		var uom_dayunits = $("#unitstotalDiv_"+asgnrowid).val();
		if (uom_dayunits == '') {
			uom_dayunits = 0.00;
		}
		var total_uomunits = $("#final_totalunits").text();
		var final_uomunits = parseFloat(total_uomunits)-parseFloat(uom_dayunits);
		if(final_uomunits < 0) {
			$("#final_totalunits").html("0.00");
			$("#f_totalunits").val("0.00");
		}
		else
		{
			$("#final_totalunits").html(final_uomunits.toFixed(2));
			$("#f_totalunits").val(final_uomunits.toFixed(2));
		}
	}
	$('form').attr('autocomplete', 'off');	
	var regex = /^(.*)(\d)+$/i;
	if (mode == 'create')
	{
		var cindex = $('#MainTable tr.tr_clone').length-1;
	}
	else
	{
		var cindex = $('#dynrowcount').val();
	}
	$("a").on('click', function(){

		var clickText = $(this).text();
		var loc = $(location).attr('href');
		var ln=loc.indexOf("/include/uom_timesheet.php");

		if (ln > -1 && clickText == 'Add Row')
		{
			cloneRow();
		}
		if (ln > -1 && clickText == 'Delete Row')
		{
			delCloneRow();
		}
	});
	
	function cloneRow()
	{
		// DayWeek Tab functionality		
		var dayWeekTrClass = getSelectedTab();
		
		var $tr    = $('#MainTable tbody>'+dayWeekTrClass+':last').closest(dayWeekTrClass);
		$tr.find('.select2-select').select2('destroy'); // Un-instrument original row
		
		var hiddenBillable = $('#hiddenBillable').val();
		var hiddenBillableArr = hiddenBillable.split(',');
		var $clone = $tr.clone(true);
		var counter = 0;
		cindex++;
		$clone.find('input[type=text]').val('');
		$clone.find('input[type=hidden]').val('');
		$clone.find(':checkbox').attr('checked',false);	
		$clone.find("select").each(function() { this.selectedIndex = 0; });
		$clone.find('label.textwrampnew').empty();
		$clone.find('#daytotalhrs_'+cindex).html('0.00');
		$clone.find("label.taskLabel").attr("onClick","javascript:AddTaskDetails('"+(cindex)+"')");
		$clone.find("font.taskLabel").attr("onClick","javascript:AddTaskDetails('"+(cindex)+"')");
		$clone.attr('id', 'row_'+(cindex) ); //update row id if required
	
		//update ids of elements in row
		$clone.find("*").each(function() {
			var id = this.id || "";
			if(id!=''){
				var splitid = id.split('_');
				var rowid = splitid.pop();
				
				var fstr = splitid.join('_');
				if(fstr!='')
				{
					if (fstr.indexOf('edit_snos') == 0)
					{
						
						var snoname = $(this).attr('name');
						var cindexpre = cindex-1;
						checkmyname1 = snoname.replace(/(\d+)/g, cindex);
						$(this).attr('name', checkmyname1);
					}
					if (fstr.indexOf('check') > -1)
					{
						var checkname = $(this).attr('name');
						var cindexpre = cindex-1;
						checkmyname1 = checkname.replace(/(\d+)/g, cindex);
						$(this).attr('name', checkmyname1);
					}
					
					if (fstr.indexOf('daily_rate_') > -1)
					{
						if (fstr.indexOf('daily_rate_pay_') == -1 && fstr.indexOf('daily_rate_bill_') == -1)
						{								
							var myname = $(this).attr('name');
							var cindexpre = cindex-1;
							var flag = false;
							
							myname1 = myname.replace(/(\d+)/, cindex);
							myname2 = myname1.replace(/(\[\d+\])/, "["+cindex+"]");
							$(this).attr('name', myname2);
							if(myname2.indexOf('daily_rate_billable_') > -1)
							{
								if(hiddenBillableArr[counter] == 'Y')
								{
									flag = true;
								}
								else
								{
									flag = false;
								}
								$(this).attr('checked', flag);
								counter++;
							}
							
							var mykeyup = $(this).attr('onkeyup');
							if(typeof(mykeyup) != "undefined")
							{
								var mykeyupsplit = mykeyup.split(',');
								var mykeyupfinal = mykeyupsplit[0]+','+cindex+','+mykeyupsplit[2];
								var mykeyupsplit = mykeyupsplit[3].split('_');
								var mykeyupsplitpop =  mykeyupsplit.pop();
								var mykeyupsplitpopjoin = mykeyupsplit.join('_');
								var mykeyupsplitpopjoinval = mykeyupsplitpopjoin+'_'+cindex+'\')';
								var mykeyupfinal1 = mykeyupfinal+','+mykeyupsplitpopjoinval;
								$(this).attr('onkeyup', mykeyupfinal1);
							}
						}
						else
						{								
							var myname0 = $(this).attr('name');
							var splitname = myname0.split('_');
							var rowname = splitname.pop();
							var fstrname = splitname.join('_');
							var myname3 = fstrname+'_'+(cindex);
							$(this).attr('name', myname3);
						}
					}
					if (fstr.indexOf('daily_dates') > -1)
					{
						var mydate = $(this).attr('name');
						mydate1 = mydate.replace(/(\[\d+\]$)/, "["+cindex+"]");
						$(this).attr('name', mydate1);
					}
					if (fstr.indexOf('daily_assignemnt') > -1)
					{
						var myasgn = $(this).attr('name');
						myasgn1 = myasgn.replace(/(\[\d+\]$)/, "["+cindex+"]");
						$(this).attr('name', myasgn1);
					}
					if (fstr.indexOf('addtaskdetails') > -1 || fstr.indexOf('textlabel') > -1)
					{
						var mytask = $(this).attr('onclick');
						mytask1 = mytask.replace(/(\(\d+\)$)/, "("+cindex+")");
						$(this).attr('onclick', mytask1);
					}
					if (fstr.indexOf('np') > -1)
					{
							var tname = $(this).attr('name');						
							tname1 = tname.replace(/(\[\d+\]$)/, "["+cindex+"]");	
							$(this).attr('name', tname1);
					}
					if (fstr.indexOf('daily_classes') > -1)
					{
						var myclass = $(this).attr('name');
						myclass1 = myclass.replace(/(\[\d+\]$)/, "["+cindex+"]");
						$(this).attr('name', myclass1);
					}
					this.id = fstr+'_'+(cindex);
					
				}					 
			}
		}); 
		$tr.after($clone);
		
		//calling the new function for binding new UI
		$tr.find('.select2-select').select2({minimumResultsForSearch: -1}); // Re-instrument original row

		$clone.find('.select2-select').select2({minimumResultsForSearch: -1}); // Instrument clone
		
		var dynrowcount = $('#dynrowcount').val();
		$('#dynrowcount').val(parseInt(dynrowcount)+1);
		chainNavigation(); // Arrow UP/Down Reload
		
		//To get the selected assignment rates when new row is added
		getRateTypes("daily_assignemnt_"+cindex);
		
		//Placeholders for add row START
		
		get_cur_placeholders("daily_assignemnt_"+cindex,cindex);
		
		//Placeholders for add row END

	}
	
	$('.addtaskdetails').blur(function() {  
	     var id = $(this).attr('id');
		 id = id.replace("np_", "");
		 
         if ($.trim(this.value) == ''){  
			 this.value = (this.defaultValue ? this.defaultValue : '');
			 $("#textlabel_"+id).html("");
			 $("#np_"+id).val("");
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
			id = id.replace("np_", "");
			 
			 if ($.trim(this.value) == ''){  
				 this.value = (this.defaultValue ? this.defaultValue : '');
				 $("#textlabel_"+id).html("");
				 $("#np_"+id).val("");
			 }
			 else
			 {
				 $("#textlabel_"+id).html(this.value);
			 }
			 
			 $(this).hide();
			 $("#textlabel_"+id).show();
		  }
	  });	
	// Arrow UP/Down Main Function
	$("#MainTable tr.tr_clone input[type=text][max_length='5']" ).each( function( i, el ) {
	   var iclass = $(el).attr('class');
	   var class_arr = iclass.split(" ");
		
		$(':input.'+class_arr[0]).bind('focus', function() {
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
	});

	chainNavigation();
	
	$('a').click(function(){		
		var me = $(this);
		me.attr("id", "timesubmit");
	});
	
	//Placeholders for UOM timesheet 
	
	
	var editMode = $('#edit_mode').val();
	if(editMode !=''){
		var u=1;
	}else{
		var u=0;
	}
	
	var da_len = $('.daily_assignemnt').length;
	
	$('.daily_assignemnt').each(function(){
		if(da_len >= u){
			get_cur_placeholders("daily_assignemnt_"+u,u);
		}
		u++;
	});
	//Edit timesheet --totals START
	if(editMode =='edit'){
		var ratesLength = "<?php echo $rate_types_count; ?>";
		var total_hrs = 0.00;var total_miles = 0.00;var total_units = 0.00;var total_days = 0.00;
		for(var r=0;r<ratesLength;r++){
			var rowno=1;
			var totHrs = 0.00;var totMiles = 0.00;var totUnits = 0.00;var totDays = 0.00;
			$('.timesheetRate'+r).each(function(){
			var attr_uom = this.getAttribute('rate_uom');
				if(this.value !=''){
					if(attr_uom == 'Hours'){
						totHrs=parseFloat(totHrs) + parseFloat(this.value);
					}
					if(attr_uom == 'Miles'){
						totMiles=parseFloat(totMiles) + parseFloat(this.value);
					}
					if(attr_uom == 'Units'){
						totUnits=parseFloat(totUnits) + parseFloat(this.value);
					}
					if(attr_uom == 'Day'){
						totDays=parseFloat(totDays) + parseFloat(this.value);
					}
				}
				var row_id = $(this).attr('id');
				var row_id_arr= row_id.split("_");
				var row_idno = row_id_arr[3];
				TotalDay_TimesheetCalc(row_idno,ratesLength,rowfocus='');
				rowno++;
			});
			
			$('#timesheetRate'+r+'_div').html(NumberFormatted(totHrs));
			$("#MainTable input[name=timesheetRate"+r+"_input]").val(NumberFormatted(totHrs));
			
			$('#timesheetRate'+r+'_day_div').html(NumberFormatted(totDays));
			$("#MainTable input[name=timesheetRate"+r+"_day_input]").val(NumberFormatted(totDays));
			
			$('#timesheetRate'+r+'_mile_div').html(NumberFormatted(totMiles));
			$("#MainTable input[name=timesheetRate"+r+"_mile_input]").val(NumberFormatted(totMiles));
			
			$('#timesheetRate'+r+'_unit_div').html(NumberFormatted(totUnits));
			$("#MainTable input[name=timesheetRate"+r+"_unit_input]").val(NumberFormatted(totUnits));
			

			total_hrs =parseFloat(total_hrs) + parseFloat(totHrs);
			total_miles =parseFloat(total_miles) + parseFloat(totMiles);
			total_units =parseFloat(total_units) + parseFloat(totUnits);
			total_days =parseFloat(total_days) + parseFloat(totDays);
		}
		$("#f_totalhrs").val(NumberFormatted(total_hrs));
		$("#final_totalhrs").html(NumberFormatted(total_hrs)); 
		
		$("#f_totaldays").val(NumberFormatted(total_days));
		$("#final_totaldays").html(NumberFormatted(total_days)); 
		
		$("#f_totalmiles").val(NumberFormatted(total_miles));
		$("#final_totalmiles").html(NumberFormatted(total_miles)); 
		
		$("#f_totalunits").val(NumberFormatted(total_units));
		$("#final_totalunits").html(NumberFormatted(total_units)); 
		
		grand_total();
		
	}
	//Edit timesheet --totals END
	
	//DayWeek tabs changes
	if (mode!='edit')
	{
		//DayWeek tabs changes
		
		$("#dayWeekBtn1").click(function(){
			var activeDayWeekTab = $("#activeDayWeekTab").val();
			var totRHours		= document.getElementById("f_totalhrs").value;
			var totR_uomdays	= document.getElementById("f_totaldays").value;
			var totR_uommiles	= document.getElementById("f_totalmiles").value;
			var totR_uomunits	= document.getElementById("f_totalunits").value;
			if((totRHours==0 || totRHours=="0.00") && (totR_uomdays==0 || totR_uomdays=="0.00") && (totR_uommiles==0 || totR_uommiles=="0.00") && (totR_uomunits==0 || totR_uomunits=="0.00")) // when hours are not enterred in the daily tab
			{			
					$(".dayWeekTab1").show();
					$(".dayWeekTab2").hide();
					$("#dayWeekBtn1").addClass("activeLeft");
					$("#dayWeekBtn2").removeClass("activeLeft");		
					$("#activeDayWeekTab").val("Day");
					reCalDayWeekTotals();
			}
			else{
				if (activeDayWeekTab == "Week") {
					var confirmTabChange = confirm("Hours are already captured in Range tab. Switching to Daily tab will not capture the hours in Range tab.\nClick on OK to continue or Cancel to return.");
					if(confirmTabChange == true){
							$(".dayWeekTab1").show();
							$(".dayWeekTab2").hide();
							$("#dayWeekBtn1").addClass("activeLeft");
							$("#dayWeekBtn2").removeClass("activeLeft");		
							$("#activeDayWeekTab").val("Day");
							reCalDayWeekTotals();
					}
					else
					{
						return;
					}
				}
			}
			
		});
		$("#dayWeekBtn2").click(function(){
			var activeDayWeekTab = $("#activeDayWeekTab").val();
			var totRHours		= document.getElementById("f_totalhrs").value;
			var totR_uomdays	= document.getElementById("f_totaldays").value;
			var totR_uommiles	= document.getElementById("f_totalmiles").value;
			var totR_uomunits	= document.getElementById("f_totalunits").value;
			if((totRHours==0 || totRHours=="0.00") && (totR_uomdays==0 || totR_uomdays=="0.00") && (totR_uommiles==0 || totR_uommiles=="0.00") && (totR_uomunits==0 || totR_uomunits=="0.00")) // when hours are not enterred in the daily tab
			{
					$(".dayWeekTab1").hide();
					$(".dayWeekTab2").show();
					$("#dayWeekBtn1").removeClass("activeLeft");
					$("#dayWeekBtn2").addClass("activeLeft");
					$("#activeDayWeekTab").val("Week");
					reCalDayWeekTotals();
			}
			else
			{
				if (activeDayWeekTab == "Day") {
					var confirmTabChange = confirm("Hours are already captured in Daily tab. Switching to Range tab will not capture the hours in Daily tab.\nClick on OK to continue or Cancel to return.");
					if(confirmTabChange == true){
							$(".dayWeekTab1").hide();
							$(".dayWeekTab2").show();
							$("#dayWeekBtn1").removeClass("activeLeft");
							$("#dayWeekBtn2").addClass("activeLeft");
							$("#activeDayWeekTab").val("Week");
							reCalDayWeekTotals();
					}
					else
					{
						return;
					}
				}
			}
			
		});
	}
	
	//for setting the positions of calenders
	$("#tcalico_0, #tcalico_1").click(function(){
		$("#tcal").css("position","fixed");
		$("#tcalShade").css("position","fixed");
	});
});

function delCloneRow(del_row_id)
{
	var dayhrs = 0.00;
	var uom_days = 0.00;
	var uom_miles = 0.00;
	var uom_units = 0.00;
	var colcountvalue = $("#colcount").val();
	var row_arr = Array();
	var m=0;


	var splitid = del_row_id.split('_');
	var rowid = splitid.pop();
	row_arr[m] =rowid;
	$('#check_'+rowid).attr('checked', true);// checking the hidden check box which is going to delete
			
	var cellval;
	var colsum;
	var gettotal;
			
	for(count=0; count < colcountvalue; count++)
	{
		if ($("#daily_rate_"+count+"_"+rowid).val() != '')
		{
			cellval = $("#daily_rate_"+count+"_"+rowid).val();
		}
		else
		{
			cellval = 0;
		}
		var rate_title = $("#daily_rate_"+count+"_"+rowid).attr('rate_uom');
		if(rate_title == 'Day'){
			if ($("#timesheetRate"+count+"_day_div").text() != ''){
					colsum = $("#timesheetRate"+count+"_day_div").text();
				}else{
					colsum = 0;
				}
		}
		else if(rate_title == 'Miles'){
			if ($("#timesheetRate"+count+"_mile_div").text() != ''){
					colsum = $("#timesheetRate"+count+"_mile_div").text();
				}else{
					colsum = 0;
				}
		}
		else if(rate_title == 'Units'){
			if ($("#timesheetRate"+count+"_unit_div").text() != ''){
					colsum = $("#timesheetRate"+count+"_unit_div").text();
				}else{
					colsum = 0;
				}
		}
		else{
			if ($("#timesheetRate"+count+"_div").text() != ''){
					colsum = $("#timesheetRate"+count+"_div").text();
				}else{
					colsum = 0;
				}
		}
				
		var diff =NumberFormatted( Math.abs(parseFloat(colsum)-parseFloat(cellval)));
		if(rate_title == 'Day'){
			$("#timesheetRate"+count+"_day_div").text(diff);
			$("#MainTable input[name=timesheetRate"+count+"_day_input]").val(NumberFormatted(diff));
		}
		else if(rate_title == 'Miles'){
			$("#timesheetRate"+count+"_mile_div").text(diff);
			$("#MainTable input[name=timesheetRate"+count+"_mile_input]").val(NumberFormatted(diff));
		}
		else if(rate_title == 'Units'){
			$("#timesheetRate"+count+"_unit_div").text(diff);
			$("#MainTable input[name=timesheetRate"+count+"_unit_input]").val(NumberFormatted(diff));
		}
		else{
			$("#timesheetRate"+count+"_div").text(diff);
			$("#MainTable input[name=timesheetRate"+count+"_input]").val(NumberFormatted(diff));
		}
				
	}
			
	if ($("#daytotalhrsDiv_"+rowid).val() != '')
	{
		gettotal = $("#daytotalhrsDiv_"+rowid).val();	
	}
	else
	{
		gettotal = 0;
	}
	dayhrs = Math.abs(parseFloat(dayhrs)+parseFloat(gettotal));
	//UOM rate types calculation START
	if ($("#daystotalDiv_"+rowid).val() != ''){
		gettotal_uomdays = $("#daystotalDiv_"+rowid).val();	
	}else{
		gettotal_uomdays = 0;
	}
	uom_days = Math.abs(parseFloat(uom_days)+parseFloat(gettotal_uomdays));
	if ($("#milestotalDiv_"+rowid).val() != ''){
		gettotal_uommiles = $("#milestotalDiv_"+rowid).val();	
	}else{
		gettotal_uommiles = 0;
	}
	uom_miles = Math.abs(parseFloat(uom_miles)+parseFloat(gettotal_uommiles));
	if ($("#unitstotalDiv_"+rowid).val() != ''){
		gettotal_uomunits = $("#unitstotalDiv_"+rowid).val();	
	}else{
		gettotal_uomunits = 0;
	}
	uom_units = Math.abs(parseFloat(uom_units)+parseFloat(gettotal_uomunits));
	//UOM rate types calculation END
	m = m+1;


	var inputs = new Array();
	var dayWeekTrClass = getSelectedTab();
	var totinputs = $("#MainTable "+dayWeekTrClass+" input.chremove[type=checkbox]").length;
	var totcheckedInputs = $("#MainTable "+dayWeekTrClass+" input.chremove[type=checkbox]:checked").length;
	if(totcheckedInputs!=0){
		if(parseInt(totinputs)==parseInt(totcheckedInputs)){
			alert("Your Timesheets must have atleast one entry. You can't delete all the entries.");
			return false;
		}else
		{
			$("#MainTable input.chremove[type=checkbox]:checked").parents("tr").remove();
			var totalhrs = $("#final_totalhrs").text();
			var finalhrs = parseFloat(totalhrs)-parseFloat(dayhrs);
			$("#final_totalhrs").html(finalhrs.toFixed(2));

			$("#f_totalhrs").val(finalhrs.toFixed(2));
			
			//UOM calculation for total miles/units/days START
			var total_uomdays = $("#final_totaldays").text();
			var finalhrs = parseFloat(total_uomdays)-parseFloat(uom_days);
			$("#final_totaldays").html(finalhrs.toFixed(2));
			$("#f_totaldays").val(finalhrs.toFixed(2)); 
			
			var total_uommiles = $("#final_totalmiles").text();
			var finalhrs = parseFloat(total_uommiles)-parseFloat(uom_miles);
			$("#final_totalmiles").html(finalhrs.toFixed(2));
			$("#f_totalmiles").val(finalhrs.toFixed(2));
			
			var total_uomunits = $("#final_totalunits").text();
			var finalhrs = parseFloat(total_uomunits)-parseFloat(uom_units);
			$("#final_totalunits").html(finalhrs.toFixed(2));
			$("#f_totalunits").val(finalhrs.toFixed(2));
			//UOM calculation for total miles/units/days END
			grand_total();
			chainNavigation(); //Arrow UP/Down Reload		
		}
	}else
	{
		alert("You have to select atleast one Timesheet entry to delete from the Available List.");
		return false;
	}
	get_totalsDiv_asgnmt();
}

function get_totalsDiv_asgnmt()
{
	var hr_inc = 0;var day_inc = 0;var mile_inc = 0;var unit_inc = 0;
	$('.rates').each(function(){
		this.setAttribute('size','6');
		var attr_uom = this.getAttribute('rate_uom');
		//var uom_dis = this.disabled;
		if(attr_uom == 'Hours'){
			hr_inc++;
		}
		if(attr_uom == 'Day'){
			day_inc++;
		}
		if(attr_uom == 'Miles'){
			mile_inc++;
		}
		if(attr_uom == 'Units'){
			unit_inc++;
		}
	});
	if(hr_inc == 0){
		$('.hrsDiv').hide();
	}else{
		$('.hrsDiv').show();
	}
	if(day_inc == 0){
		$('.dayDiv').hide();
	}else{
		$('.dayDiv').show();
	}
	if(mile_inc == 0){
		$('.mileDiv').hide();
	}else{
		$('.mileDiv').show();
	}
	if(unit_inc == 0){
		$('.unitDiv').hide();
	}else{
		$('.unitDiv').show();
	}
}

// Function used to select the default tab when create/edit the timesheet.
function buildDayWeekTabs()
{
	var mode =  '<?php echo $edit_mode; ?>';
	//alert(mode);
	var dayWeekRange = '<?php echo ($range=="yes") ? "Week" : "Day"; ?>';
	if (mode=='edit')
	{
		if (dayWeekRange=='Week')
		{
			$(".dayWeekTab1").hide();
			$(".dayWeekTab2").show();
			$("#dayWeekBtn1").removeClass("activeLeft").addClass("deactiveLeft");
			$("#dayWeekBtn2").addClass("activeLeft");
			$("#activeDayWeekTab").val("Week");
		}
		else
		{
			$(".dayWeekTab1").show();
			$(".dayWeekTab2").hide();		
			$("#dayWeekBtn1").addClass("activeLeft");
			$("#dayWeekBtn2").removeClass("activeLeft").addClass("deactiveLeft");
			$("#activeDayWeekTab").val("Day");			
		}
		
	}
	else
	{
		$(".dayWeekTab1").show();
		$(".dayWeekTab2").hide();		
		$("#dayWeekBtn1").addClass("activeLeft");
		$("#dayWeekBtn2").removeClass("activeLeft");
		$("#activeDayWeekTab").val("Day");
	}    
}

// Function used to re-calculate the hours enterred in the timesheet when changing the tab
function reCalDayWeekTotals()
{
	var ratesLength = "<?php echo $rate_types_count; ?>";
	var total_hrs = 0.00;var total_miles = 0.00;var total_units = 0.00;var total_days = 0.00;
	
	var dayWeekTrClass = getSelectedTab();
	
	for(var r=0;r<ratesLength;r++){
		var rowno=1;
		var totHrs = 0.00;var totMiles = 0.00;var totUnits = 0.00;var totDays = 0.00;
		$(dayWeekTrClass+' .timesheetRate'+r).each(function(){
		var attr_uom = this.getAttribute('rate_uom');
			if(this.value !=''){
				if(attr_uom == 'Hours'){
					totHrs=parseFloat(totHrs) + parseFloat(this.value);
				}
				if(attr_uom == 'Miles'){
					totMiles=parseFloat(totMiles) + parseFloat(this.value);
				}
				if(attr_uom == 'Units'){
					totUnits=parseFloat(totUnits) + parseFloat(this.value);
				}
				if(attr_uom == 'Day'){
					totDays=parseFloat(totDays) + parseFloat(this.value);
				}
			}
			var row_id = $(this).attr('id');
			var row_id_arr= row_id.split("_");
			var row_idno = row_id_arr[3];
			TotalDay_TimesheetCalc(row_idno,ratesLength,rowfocus='');
			rowno++;
		});
		
		$('#timesheetRate'+r+'_div').html(NumberFormatted(totHrs));
		$("#MainTable input[name=timesheetRate"+r+"_input]").val(NumberFormatted(totHrs));
		
		$('#timesheetRate'+r+'_day_div').html(NumberFormatted(totDays));
		$("#MainTable input[name=timesheetRate"+r+"_day_input]").val(NumberFormatted(totDays));
		
		$('#timesheetRate'+r+'_mile_div').html(NumberFormatted(totMiles));
		$("#MainTable input[name=timesheetRate"+r+"_mile_input]").val(NumberFormatted(totMiles));
		
		$('#timesheetRate'+r+'_unit_div').html(NumberFormatted(totUnits));
		$("#MainTable input[name=timesheetRate"+r+"_unit_input]").val(NumberFormatted(totUnits));
		

		total_hrs =parseFloat(total_hrs) + parseFloat(totHrs);
		total_miles =parseFloat(total_miles) + parseFloat(totMiles);
		total_units =parseFloat(total_units) + parseFloat(totUnits);
		total_days =parseFloat(total_days) + parseFloat(totDays);
	}
	$("#f_totalhrs").val(NumberFormatted(total_hrs));
	$("#final_totalhrs").html(NumberFormatted(total_hrs)); 
	
	$("#f_totaldays").val(NumberFormatted(total_days));
	$("#final_totaldays").html(NumberFormatted(total_days)); 
	
	$("#f_totalmiles").val(NumberFormatted(total_miles));
	$("#final_totalmiles").html(NumberFormatted(total_miles)); 
	
	$("#f_totalunits").val(NumberFormatted(total_units));
	$("#final_totalunits").html(NumberFormatted(total_units)); 		
	grand_total();
}
$(document).ready(function(){
	$hide_billable_checkbox = $("#hide_billable_checkbox").val();
  //alert($hide_billable_checkbox);
  if($hide_billable_checkbox){
	$('.container-chk').hide();
  }
});
</script>
</body>
</html>
