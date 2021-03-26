<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<?php
	require("global.inc");
	require("dispfunc.php");

	require("Menu.inc");
	$menu=new EmpMenu();

	$Supusr = superusername();
	$page15 = $_SESSION[page15.$ACC_AS_SESSIONRN];
	$page215 = $_SESSION[page215.$ACC_AS_SESSIONRN];

	session_unregister($_SESSION["schdet".$ACC_AS_SESSIONRN]);
	session_unregister($_SESSION["assignment_mulrates".$ACC_AS_SESSIONRN]);
	$_SESSION[assignment_mulrates.$ACC_AS_SESSIONRN] = "";
	$assignment_mulrates = $_SESSION[assignment_mulrates.$ACC_AS_SESSIONRN];

	$enames="";
    //for retrieving the locations based on the employee id--Akkupay for employee management new assignment creatin
    $query="SELECT username,name, ".getEntityDispName("sno","name").",sno FROM emp_list WHERE lstatus != 'DA' AND username = '".$new_user."' AND lstatus != 'INACTIVE' AND (empterminated!='Y' || UNIX_TIMESTAMP(IF(tdate='' || tdate IS NULL,NOW(),tdate))>UNIX_TIMESTAMP(NOW())) ORDER BY name";
	$result=mysql_query($query,$db);
    $myrow=mysql_fetch_row($result);
    $candID = $myrow[3];

	// checking any active assignments are there are not.
        $que="select username,count(*) from hrcon_jobs where ustatus='active' and jtype='OP' group by username";
	$res=mysql_query($que,$db);
	while($rsassign=mysql_fetch_array($res))
	{
		$arr[$rsassign[0]]=$rsassign[1];
	}

	// checking any active compensations are there are not.
	$que="select emptype,username from hrcon_compen where ustatus='active' group by username";
	$res=mysql_query($que,$db);
	while($rscompen=mysql_fetch_array($res))
	{
		$sarr[$rscompen[1]]=$rscompen[0];
	}

	$uservals=explode("','",$usernamevals);
	$cnt=count($uservals);
	for($i=0;$i<$cnt;$i++)
	{
		$rsassign.=",".$uservals[$i]."|".$arr[$uservals[$i]]."|".$sarr[$uservals[$i]];
	}

	function sel11($a,$b)
	{
		if($a==$b)
			return "checked";
		else
			return "";
	}

	function sele11($a,$b)
	{
		if($a==$b)
			return "selected";
		else
			return "";
	}

	if($page215=="OP")
	{
		session_unregister($_SESSION["Page215ass".$ACC_AS_SESSIONRN]);
		$_SESSION[Page215ass.$ACC_AS_SESSIONRN]="";

		session_unregister($_SESSION["Page215ass".$ACC_AS_SESSIONRN]);
		$_SESSION[Page215ass.$ACC_AS_SESSIONRN]="";
	}
	
	$Page215ass = $_SESSION[Page215ass.$ACC_AS_SESSIONRN];

	if($addr=="client")
		$elements[1]=$client;
	
	$date=explode("-",$elements[2]);
	$date1=explode("-",$elements[3]);

    for($i=1;$i<8;$i++)
        $sunc[$i]="";

	if($elements[28]!="")
	{
		$wda=explode(":",$elements[28]);
		$n=count($wda);
		for($i=0;$i<$n;$i++)
		{
			switch((int)$wda[$i])
			{
				case 1 :
					$sunc[1]=1;
					break;
				case 2 :
					$sunc[2]=2;
					break;
				case 3 :
					$sunc[3]=3;
					break;
				case 4 :
					$sunc[4]=4;
					break;
				case 5 :
					$sunc[5]=5;
					break;
				case 6 :
					$sunc[6]=6;
					break;
				case 7 :
					$sunc[7]=7;
					break;
			}
		}
	}

	function sel($a,$b)
	{
		if($a==$b)
			return "checked";
		else
			return "";
	}

	function sele($a,$b)
	{
		if($a==$b)
			return "selected";
		else
			return "";
	}
	
	$spl_Attribute = (PAYROLL_PROCESS_BY_MADISON=='MADISON') ? 'udCheckNull ="YES" ' : '';
	
	//Defining a variable for showing mandatory SyncHR star marks from this page only.
	$showMandatoryAstrik = "Y";
	
	//Get the shift scheduling old/new display status based on the option set in admin for the new assignments
	$schedule_display = 'OLD';
	if(SHIFT_SCHEDULING_ENABLED == 'Y')
	{
		$schedule_display = 'NEW';
	}
	
?>
<html>
<head>
<title>New Assignment</title>
<style>
.alert-cntrbtns { 
	margin-left:120px; 
	text-align:center; 
	margin-bottom:7px; 
}
.cdfCustTextArea {
    width: 390px !important;
}
.cdfJoborderBlk .select2-container, .cdfJoborderBlk .select2-drop, .cdfJoborderBlk .select2-search, .cdfJoborderBlk .select2-search input {
    width: 300px !important;
}
</style>
<link href="/BSOS/Home/style_screen.css" rel="stylesheet" type="text/css">
<link href="/BSOS/css/educeit.css" rel="stylesheet" type="text/css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/tab.css">
<script src=/BSOS/scripts/tabpane.js></script>
<script language=javascript src=/BSOS/scripts/validatehresume.js></script>
<script language=javascript src=/BSOS/scripts/validatehhr.js></script>
<script language=javascript src=/BSOS/scripts/validateremphr.js></script>
<script language=javascript src=scripts/validateasn.js></script>
<script language=javascript src=/BSOS/scripts/validateaempresume.js></script>
<script language=javascript src=/BSOS/scripts/jquery-min.js></script>
<script>
	var madison='<?=PAYROLL_PROCESS_BY_MADISON;?>';
	var syncHRDefault = '<?php echo DEFAULT_SYNCHR; ?>';
         var akkupayroll = '<?php echo DEFAULT_AKKUPAY ;?>';
</script>

<script type="text/javascript">
var asgnAlertStartDate = "<?php echo date('m/d/Y');?>";
var asgnAlertEndDay = "<?php echo date('d');?>";
var asgnAlertEndMonth = "<?php echo date('m');?>";
var asgnAlertEndYear = "<?php echo date('Y');?>"; 
</script>
 <style>
 .subPadL-0{padding: 0px !important;}
 @media all and (-ms-high-contrast: none), (-ms-high-contrast: active) {
 .summaryform-formelement{height: 33px !important;}
}
.customProfile input[type="radio"]{width: inherit !important;margin: 0px 4px !important;}
.cdfAutoSuggest select{min-width: 250px !important;}
.cdfJoborderBlk .select2-container, .cdfJoborderBlk .select2-drop, .cdfJoborderBlk .select2-search, .cdfJoborderBlk .select2-search input {
    width: 250px !important;
}
#multipleRatesTab .crmsummary-jocomp-table select, #multipleRatesTab .crmsummary-jocomp-table input{width: 100px !important;min-width: 100px !important;}
#multipleRatesTab .crmsummary-jocomp-table input[type="radio"]{width: inherit !important;margin: 0px 4px !important;min-width:inherit !important;}
.timegrid{ width:2.052% !important}
.summarytext input[type="checkbox"]{margin: 5px 2px!important;}
@media screen\0 {	
	/* IE only override */
.summaryform-formelement{ height:18px; font-size:11px !important; }
a.crm-select-link:link{ font-size:11px !important; }
a.edit-list:link{ font-size:10px !important;}
.summaryform-bold-close-title{ font-size:9px !important;}
.center-body { text-align:left !important;}
.crmsummary-jocomp-table td{ font-size:9px !important ; text-align:left !important;}
.summaryform-nonboldsub-title{ font-size:9px}
#smdatetable{ font-size:11px !important;}
.summaryform-formelement{ text-align:left !important; vertical-align:middle}
.crmsummary-content-title{ text-align:left !important}
.crmsummary-edit-table td{ text-align:left !important}
.summaryform-bold-title{ font-size:10px !important;}
.summaryform-nonboldsub-title{ font-size:10px !important;}
.smdaterowclass td, .timehead{ font-size:11px !important;}
.managesymb { padding-top: 3px !important;}
}
@media screen and (-webkit-min-device-pixel-ratio:0) { 
    /* Safari only override */
    ::i-block-chrome, .timegrid { width:2.07% !important;}
	::i-block-chrome, .timehead { width:4%;}   

}

.managesymb { margin: 2px 4px !important; }
.closebtnstyle{ float:left; margin-top:1px; *margin-top:3px; vertical-align:middle; }
.alert-ync-text {
    font-family: arial !important;
    font-size: 14px !important;
    margin-top: 0 !important;
}
.alert-cntrbtns {
    margin-left: 145px !important;
}
.alert-ync-text span {
    font-weight: normal !important;
}
.modalDialog_contentDiv{
	height: 300px !important;
    left: 50% !important;
    margin-left: -350px !important;
    margin-top: -225px !important;
    top: 50% !important;
}
.modalDialog_contentDivDynClass{
	height: 300px !important;
    left: 50% !important;
    margin-left: -200px !important;
    margin-top: -225px !important;
    top: 50% !important;
}

.modalDialog_contentDivDynClass{height:auto !important; top:25% !important; position: absolute !important; margin-top: 0px !important; }

.modalDialog_contentDiv{height:auto !important; top:25% !important; position: absolute !important; margin-top: 0px !important; }
.alert-ync-container{ padding-bottom: 10px !important; height: inherit !important; }

 @media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {  
 .modalDialog_contentDiv{height:auto !important; top:25% !important; position: absolute !important; }
}
 @media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {  
 .modalDialog_contentDivDynClass{height:auto !important; top:25% !important; position: absolute !important; }
}

@media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {
 #readMoreShiftData{float: left !important; margin-left: 50% !important;}
}
</style>
<?php
if(PAYROLL_PROCESS_BY_MADISON=='MADISON')
	echo "<script language=javascript src=/BSOS/scripts/formValidation.js></script>";
?>
</head>
<body>
<form method=post name=conreg id=conreg action=newconreg15.php>
<input type=hidden name=url>
<input type=hidden name=dest>
<input type=hidden name=daction value='storeresume.php'>
<input type=hidden name=page15 value='<?php echo $page15;?>'>
<input type=hidden name=page13 value='<?php echo $page13;?>'>
<input type=hidden name=page215 value='<?php echo $page215;?>'>
<input type=hidden name=addr value="<?php echo $addr;?>">
<input type=hidden name=Page215ass value='<?php echo $Page215ass;?>'>
<input type="hidden" name="assign" id="assign" value="New">
<input type="hidden" name="hdnassign" id="hdnassign" value="New">

<input type="hidden" name="conusername" id="conusername" value="">
<input type="hidden" name="hdnAssid" id="hdnAssid" value="">

<input type=hidden name='hterminate' id='hterminate' value="" />
<input type=hidden name='hcloseasgn' id='hcloseasgn' value="" />
<input type=hidden name='hterdate' id='hterdate' value="" />
<input type=hidden name='henddate' id='henddate' value="" />
<input type="hidden" name="ACC_AS_SESSIONRN" id="ACC_AS_SESSIONRN" value="<?php echo $ACC_AS_SESSIONRN;?>">

<?php
	$thisday=mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"));
	$todate=date("m/d/Y",$thisday);
?>
<input type=hidden name=dateval value="<?php echo $todate;?>">
<input type="hidden" name='Supuser' value="<?php echo $Supusr;?>">
<input type="hidden" name="sm_form_data" id="sm_form_data" value="" />
<input type="hidden" name="sm_enabled_option" id="sm_enabled_option" value="<?php echo $schedule_display; ?>" />
<input type="hidden" name="theraphySourceEnable" id="theraphySourceEnable" value="<?php echo THERAPY_SOURCE_ENABLED;?>" />
<div id="main">
<td valign=top align=center>
<table width=99% cellpadding=0 cellspacing=0 border=0>
	<div id="content">
	<tr>
		<td>
		<table width=99% cellpadding=0 cellspacing=0 border=0>
		<tr>
		<?php
		if($command != "new")
		{
			?>
			<td align=left><font class=modcaption>&nbsp;&nbsp;<?php $names=explode("|",$page1); echo dispTextdb($names[0])." ".dispTextdb($names[2]); ?></font></td>
			<?php
		}
		else
		{
			?>
			<td align=left><font class=modcaption>&nbsp;&nbsp;Accounting Management</font></td>
			<?
		}
		?>
		</tr>
		</table>
		</td>
	</tr>
	</div>

	<div id="grid_form">
	<table border="0" width="100%" cellspacing="5" cellpadding="0" bgcolor="white">
	<tr>
		<td width=100% valign=top align=center>
		<div class="tab-pane" id="tabPane2">	
		<?php
			$modfrom="newasgmt";
			$assignmentStatus = "newassignment";
			$assg_disable="";
			$mode="newassign";

			if(PAYROLL_PROCESS_BY_MADISON == "MADISON")
				$elements[21] = "HOUR";
				$mod = 7;
				
			$apprn = $ACC_AS_SESSIONRN;
			require($app_inc_path."assignment.php");
		?>
		</td>
	</tr>
	</table>
	</div>
</table>
</td>
</div>
</form>
</body>
</html>