/*
	Modifed Date : July 26, 2010.
	Modified By  : Suneel Kumar.
	Purpose      : Added the alert message for Employee Search and select window, single employee select and view date range
	TS Task Id   : 5161, (Rpstaffing) Need to provide alert message if an employee submits timesheets within the same date range again.

	Modifed Date : July 14, 2010.
	Modified By  : Kumar Raju K.
	Purpose      : Changed the alert message description for MyProfile module.
	TS Task Id   : 5161, (Rpstaffing) Need to provide alert message if an employee submits timesheets within the same date range again.
	
	Modifed Date : July 08, 2010.
	Modified By  : Prasadd.
	Purpose      : Added alert messages resubmitting the same date range.
	TS Task Id   : 5161.
	
	Modifed Date : Feb 18, 2009.
	Modified By  : Prasadd.
	Purpose      : Removed alert messages for view link case.
	TS Task Id   : 4969.
	
	Modifed Date : Dec 11, 2009.
	Modified By  : Prasadd.
	Purpose      : To implement Class, hours type, hours-multiple rates spec.
	TS Task Id   : 4823,4820,4824.
	
	TS ID: Bug 3009 
	By: Hari
	Date: May 31 2008
	Details: Accounting Timesheets - Need to add column for Double Time hours	
*/	
var totalRegHours=0;
var totalOvrHours=0;
var totalDovrHours=0;
var totEmpSel=0;
var totEmpSelTS=0;
var totEmpSelTS_UOM=0;
var daterangehours = false;
var dateRangeFilled = "NO";
var elehref;
timesheetSubmitCheckAlert = function(form){
	showSaveAlert();
	return ;
}

//New functions to show alerts for invitation send or don't send
function showSaveAlert()
{
	var obj  = document.getElementById("dynsndiv");
	var obj1 = document.getElementById("SaveAlert");
	var v_width  = 710;
	var v_heigth = 424;
	var top=(window.screen.availHeight-v_heigth)/2;
	var left=(window.screen.availWidth-v_width)/2;
	
	with(obj)
	{
		style.top = "0px";
		style.left = "0px";
		if(navigator.appName == 'Microsoft Internet Explorer'){
			style.width  = window.document.body.clientWidth;
			style.height = window.document.body.clientHeight;
		} else {
			style.width  = "100%";
			style.height = "100%";
		}
		
		style.zIndex = "99";
		style.position = "absolute";
		style.filter = "alpha(opacity=30)";
		style.backgroundColor = "#AAAAAA";
		style.opacity = ".3";
		style.display = "block";
	}	
	with(obj1)
	{
		style.position = "absolute";
		style.top = top+"px";
		style.left = left+"px";
		style.zIndex = 2000;
		style.visibility = "visible";
		style.display = "block";
		var displayHTMCode = '<table style="width:100%; height:95%; background-color:#FFFFFF;" border="0"><tr valign="middle"><td width="99%" style="text-align:center;"><font style="font-family:Arial, Helvetica, sans-serif; size=12px"; >Processing, Please wait...</font><br /><br /><img src=\'/BSOS/images/loading_icon_small.gif\' align=middle /></td><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert(\'-1\');" class="time-alert-button" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></tr></table>';
		obj1.innerHTML = displayHTMCode;
	}
}

getConfirmAlert = function(status){
	form=document.sheet;
	var act = form.aa.value;
	var v_heigth = 300;
	var v_width  = 600;
	var top1=(window.screen.availHeight-v_heigth)/2;
	var left1=(window.screen.availWidth-v_width)/2;
	switch (status){
		case '1':
			singleTimesheetSubmit();
		break;
		
		case '2':
			form.submit();
		break;
		case '3':
			multipleTimesheetOptNo();
		break;
		case '-1':
		break;
	}
	document.getElementById("dynsndiv").style.display = "none";
	document.getElementById("SaveAlert").style.display = "none";
}

function multipleTimesheetOptNo()
{
   form=document.sheet;
   var existTimesheetID = form.chksnoid.value;
   var existchkarr = existTimesheetID.split(',');
   var e = document.getElementsByName('auids[]');
   var totalchkbox = e.length;
   var val;
   for(var i=0; i<totalchkbox; i++)
   {
	val = e[i].value;
	var chkid = val.split('_');
	   if(!chkArrayVAl(existchkarr,chkid[1]))
	   {
		   	e[i].checked=true;
	   }
   }
   return;
}

	
	
function attatchopen(url,name)
{
	var v_heigth = 400;
	var v_width  = 600;
	var top1=(window.screen.availHeight-v_heigth)/2;
	var left1=(window.screen.availWidth-v_width)/2;
	var remoter = window.open(url,name,"width="+v_width+"px,height="+v_heigth+"px,resizable=yes,scrollbars=yes,left="+left1+"px,top="+top1+"px,status=0");
	remoter.focus();
	return;
}

function isSpl(field)
{
	var str = field.value;
	for (var i = 0; i < str.length; i++)
	{
		var ch = str.substring(i, i + 1);
		if ( ((ch < "a" || "z" < ch) && (ch < "A" || "Z" < ch))&&(ch!=" ") && (ch < "0" || "9" < ch) && (ch!="&") && (ch!="'")&& (ch!="."))
		{
			return true;
		}
	}
	return false;
}

function doCancel1()
{
    window.location.href="emphis.php";
}

function CheckSpecChars(field)
{
	var str = field.value;
	for (var i = 0; i < str.length; i++)
	{
		var ch = str.substring(i, i + 1);
		if ( (ch=="^" || ch=="|" ))
		{
   			return true;
		}
	}
	return false;
}

function chk_clearTop()
{
	var e = document.sheet.elements;

	for (var i=0; i < e.length; i++)
	{
		if (e[i].name == "auids[]")
		{

			if (e[i].checked == false)
			{

                document.sheet.chk.checked=false;
                return;
             }
        }
    }
    for (var i=0; i < e.length; i++)
	{
		if (e[i].name == "auids[]")
		{

			if (e[i].checked == true)
			{

                document.sheet.chk.checked=true;
                return;
             }
        }
    }
}

function doCancel4()
{
	document.location.href = "empfaxhis.php";
}

function ShowDet(val)
{
    var form=document.forms[0];
    form.addr1.value=val;
    form.action="showfxdet.php";
    form.submit();
}

function DateSelector(val)
{
	form=document.sheet;
	var date="//";
	if(val=="serv")
		date=form.servicedate.value;
	if(val=="servto")
		date=form.servicedateto.value;

	sindate=date.split("/");
	date_flag =  0
	if(!check_dateFormat(date))
	{
		alert(date_error);
		date_flag = 1
	}
	if(date_flag == 0)
	{
		var mn=sindate[0];
		var dy=sindate[1];
		var yr=sindate[2];
		// calculate window center positions
		var v_width  = 200;
		var v_heigth = 200;
		var top=(window.screen.availHeight-v_heigth)/2;
		var left=(window.screen.availWidth-v_width)/2;	
		remote=window.open('calendar.php?mn='+mn+'&dy='+dy+'&yr='+yr+'&val='+val,'cal','width=200,height=200,resizable=no,scrollbars=no,status=0,left='+left+',top='+top);
		remote.focus();
	}
}
function DateSelectorMulti(val)
{
	form=document.sheet;
	var date="//";
	if(val=="servfrom")
		date=form.servicedatefrom.value;
	else if(val=="servto")
		date=form.servicedateto.value;
	sindate=date.split("/");
	date_flag =  0
	form.val.value = val;
	if(!check_dateFormat(date))
	{
		alert(date_error);
		date_flag = 1
	}
	if(date_flag == 0)
	{
		var mn=sindate[0];
		var dy=sindate[1];
		var yr=sindate[2];
		// calculate window center positions
		var v_width  = 200;
		var v_heigth = 200;
		var top=(window.screen.availHeight-v_heigth)/2;
		var left=(window.screen.availWidth-v_width)/2;	
		remote=window.open('/BSOS/Accounting/Time_Mngmt/calendarmulti.php?mn='+mn+'&dy='+dy+'&yr='+yr+'&val='+val,'cal','width=200,height=200,resizable=no,scrollbars=no,status=0,left='+left+',top='+top);
		remote.focus();
	}
}

function DateSelector1(val)
{
	form=document.forms[0];
	var date="//";
	if(val=="serv")
               date=form.servicedate.value;
	if(val=="servto")
               date=form.servicedateto.value;	
	date_flag =  0
	if(!check_dateFormat(date))
	{
		alert(date_error);
		date_flag = 1
	}
	if(date_flag == 0)
	{			   
		sindate=date.split("/");
		var mn=sindate[0];
		var dy=sindate[1];
		var yr=sindate[2];
		// calculate window center positions
		var v_width  = 200;
		var v_heigth = 200;
		var top=(window.screen.availHeight-v_heigth)/2;
		var left=(window.screen.availWidth-v_width)/2;
	
		remote=window.open('../calendar.php?mn='+mn+'&dy='+dy+'&yr='+yr+'&val='+val,'cal', 'width=200,height=200,resizable=no,scrollbars=no,status=0,left='+left+',top='+top);
		if (remote!=null)
		{
			if (remote.opener==null)
				remote.opener=self;
		}
	}
}

function addHours(h1,h2)
{
    var drr;
    var drr1;
    var hour1;
    var min1l
    var hour2;
    var min2;
    var total;
    var drem;
    var dtotal;

    var ph1=h1.toString();
    var ph2=h2.toString();

    drr=ph1.split(".");
    hour1=parseInt(drr[0],10);
    min1=parseInt(drr[1],10);

    drr1=ph2.split(".");
    hour2=parseInt(drr1[0],10);
    min2=parseInt(drr1[1],10);

    hour1=hour1+hour2;
    min1=min1+min2;
    if(min1>=60)
    {
        dres=parseInt(min1/60,10);
        drem=min1%60;
        dtotal=hour1+dres;
        if(drem<10)
            drem="0"+drem;
        total=dtotal+"."+drem;
    }
    else
    {
        if(min1<10)
        {
            min1="0"+min1;
        }
        dtotal=hour1;
        total=dtotal+"."+min1;
    }
    return total;
}

function doshow(target)
{
    var tval;
    var thrs;
    var temp;
    var tmin;
    var found=false;
    tval=target.value;
    
    if(tval.length > 0)
    {
        if(!isNaN(tval))
        {
            /* for single digit values */
            if(tval.indexOf(".")<0 )
            {
                if(Number(target.value)>=0 && Number(target.value)<10)
                {
                    target.value=target.value+":00";
                }
                else if(Number(target.value)>=10)// && Number(target.value)<=24
                {
                    target.value=target.value+":00";
                }
                /*end single digit */
            }
            else if(tval.indexOf(".")>0 )
            {
                if(Number(target.value)>=0 && Number(target.value)<10)
                {
                    temp=tval.split(".");
                    if(temp[1].length>2)
                    {
                        temp[1]=temp[1].substring(0,2);
                        found=true;
                    }

                    if(temp[1]>0 && temp[1]<10 && !found && temp[1].length<2)
                    {
                        temp[1]=temp[1]+"0";
                    }
                    temp[1]=Math.floor((Number(temp[1])/100)*60);
                    if(temp[1]<10 )
                    {
                        temp[1]="0"+temp[1];
                    }

                    target.value=temp[0]+":"+temp[1];
                }
                else if(Number(target.value)>=10) //&& Number(target.value)<=24
                {
                    temp=tval.split(".");

                    if(temp[1].length>2)
                    {
                        temp[1]=temp[1].substring(0,2);
                        found=true;
                    }

                    if(temp[1]>0 && temp[1]<10 && !found && temp[1].length<2)
                    {
                        temp[1]=temp[1]+"0";
                    }
                    temp[1]=Math.floor((Number(temp[1])/100)*60);
                    if(temp[1]<10 )
                    {
                        temp[1]="0"+temp[1];
                    }
                    target.value=temp[0]+":"+temp[1];
                }
            }
        }
        else
        {
            tval=tval.replace(":",".");
            if(!isNaN(tval))
            {
                temp=tval.split(".");
                if(temp[1]>0 && temp[1]<10 && temp[1].length<2)
                    temp[1]="0"+temp[1];
                target.value=temp[0]+":"+temp[1];
            }
        }
    }
}

function doShowT(target)
{
    //doshow(target);
	form=document.sheet;
	var val=form.rowcou.value;
	var total=0;
	var dtotal=0;
	var dhours=0
	var dmin=0;
	var darr;
	var  dres=0;
	var drem;
	var temp;
	var tval;
    for(i=1;i<=val;i++)
    {
        //tval=form.thours[i].value;
		tval=form.hours[i].value;
        //tval=tval.replace(":",".")

        if(isNaN(tval))
        {
            total=total;
        }
        else
        {
            temp=Number(tval);
           /* dtotal=temp.toFixed(2);
            drr=dtotal.split(".");
            dhours=parseInt(drr[0],10)+dhours;
            dmin=parseInt(drr[1],10)+dmin;*/
			dtotal=temp+dtotal;
			
        }
    }

    /*if(dmin>=60)
    {
        dres=parseInt(dmin/60,10);
        drem=dmin%60;
        dtotal=dhours+dres;
        if(drem<10)
            drem="0"+drem;
        totalhr.innerHTML=dtotal+":"+drem;  
        totalRegHours=dtotal+":"+drem;
    }
    else
    {
        if(dmin<10)
            dmin="0"+dmin;

        totalhr.innerHTML=dhours+":"+dmin;
        totalRegHours=dhours+":"+dmin;
    }*/
	dtotal = NumberFormatted(dtotal);
	totalhr.innerHTML=dtotal;//deepak
    totalRegHours=dtotal;
}

function doShowT1(target)
{
    //doshow(target);
	form=document.sheet;
	var val=form.rowcou.value;
	var total1=0;
	var dtotal1=0;
	var dhours1=0
	var dmin1=0;
	var darr1;
	var  dres1=0;
	var drem1;
	var temp1;
	var tval1;
	
    for(i=1;i<=val;i++)
    {
        /*tval1=form.othours[i].value;*/
		tval1=0.00;
        //tval1=tval1.replace(":",".")

        if(isNaN(tval1))
        {
            total1=total1;
        }
        else
        {
            temp1=Number(tval1);
            /*dtotal1=temp1.toFixed(2);
            drr1=dtotal1.split(".");
            dhours1=parseInt(drr1[0],10)+dhours1;
            dmin1=parseInt(drr1[1],10)+dmin1;*/
			dtotal1=temp1+dtotal1;
        }
    }

   /* if(dmin1>=60)
    {
        dres1=parseInt(dmin1/60,10);
        drem1=dmin1%60;
        dtotal1=dhours1+dres1;
        if(drem1<10)
            drem1="0"+drem1;
        othour.innerHTML=dtotal1+":"+drem1;
        totalOvrHours=dtotal1+":"+drem1;
    }
    else
    {
        if(dmin1<10)
            dmin1="0"+dmin1;

        othour.innerHTML=dhours1+":"+dmin1;
        totalOvrHours=dhours1+":"+dmin1;
    }*/
	dtotal1 = NumberFormatted(dtotal1);
	othour.innerHTML=dtotal1;//deepak
    totalOvrHours=dtotal1;
}

function doShowT2(target)
{
    //doshow(target);
	form=document.sheet;
	var val=form.rowcou.value;
	var total1=0;
	var dtotal1=0;
	var dhours1=0
	var dmin1=0;
	var darr1;
	var  dres1=0;
	var drem1;
	var temp1;
	var tval1;
	
    for(i=1;i<=val;i++)
    {
        /*tval1=form.dbhours[i].value;*/
		tval1=0.00;
        //tval1=tval1.replace(":",".")

        if(isNaN(tval1))
        {
            total1=total1;
        }
        else
        {
            temp1=Number(tval1);
            /*dtotal1=temp1.toFixed(2);
            drr1=dtotal1.split(".");
            dhours1=parseInt(drr1[0],10)+dhours1;
            dmin1=parseInt(drr1[1],10)+dmin1;*/
			dtotal1=temp1+dtotal1;
        }
    }

    /*if(dmin1>=60)
    {
        dres1=parseInt(dmin1/60,10);
        drem1=dmin1%60;
        dtotal1=dhours1+dres1;
        if(drem1<10)
            drem1="0"+drem1;
        dbhour.innerHTML=dtotal1+":"+drem1;
        totalDovrHours=dtotal1+":"+drem1;
    }
    else
    {
        if(dmin1<10)
            dmin1="0"+dmin1;

        dbhour.innerHTML=dhours1+":"+dmin1;
        totalDovrHours=dhours1+":"+dmin1;
    }*/
	 dtotal1 = NumberFormatted(dtotal1);
	 dbhour.innerHTML=dtotal1;//deepak
     totalDovrHours=dtotal1;
}
function doShowTM(target)
{
    //doshow(target);	
}

function doEdit()
{
	form = document.forms[0];
	form.action = "edittime.php";
	form.submit();
}

function doHistory()
{
    window.location.href="histimesheets.php";
}

function doCancel3()
{
    window.location.href="empfaxhis.php";
}

function doShowA(val)
{
    var form=document.forms[0];
    form.addr.value=val;
    form.action="emptimeshis.php";
    form.submit();
}

function doCancel6()
{
	document.location.href = "histimesheets.php";
}

function doShowDH(val)
{
    var form=document.forms[0];
    form.action="showdetails.php";
    form.addr1.value=val;
    form.submit();
}

function doCancel5()
{
    var form = document.forms[0];
	var navpage = timesheet.navpage.value;
	
	var rec=timesheet.rec.value;
	if( navpage == "invoice" )
	{
		var indate = timesheet.indate.value;
		var duedate=timesheet.duedate.value;
		var serfdate1=timesheet.serfdate1.value;
		var sertodate1=timesheet.sertodate1.value;
		var client=timesheet.client_acc.value;
		
		form.action="/BSOS/Accounting/Bill_Mngmt/invoice.php?stat=prev&indate="+indate+"&duedate="+duedate+"&serfdate1="+serfdate1+"&sertodate1="+sertodate1+"&client="+client+"&set=1";
		form.submit();
	}
	else if( navpage == "preview" )
	{
		form.action = "/BSOS/Accounting/Bill_Mngmt/sscinvoice.php";
		form.submit();
	}
	else if( navpage == "deliverinvoice" )
	{
		var addr = timesheet.addr.value;
		var acc = timesheet.acc.value;
		form.action ="/BSOS/Accounting/Bill_Mngmt/editinvoice.php?acc="+acc+"&addr="+addr;
		form.submit();
	}
	else if( navpage == "deliverpreview" )
	{
		form.action = "/BSOS/Accounting/Bill_Mngmt/secinvoice.php";
		form.submit();
	}
	else if( navpage == "invoicehistory" && rec=="" )
	{
		var addr = timesheet.addr.value;
		var printinvoice = timesheet.printinvoice.value;
		var acc = timesheet.acc.value;
		window.location.href = "/BSOS/Accounting/Bill_Mngmt/showinvoice.php?printinvoice="+printinvoice+"&addr="+addr+"&acc="+acc;
	}
	else if( navpage == "invoicehistory" && rec!="" )
	{
		var addr = timesheet.addr.value;
		var printinvoice = timesheet.printinvoice.value;
		var acc = timesheet.acc.value;
		var client = timesheet.client.value;
		window.location.href = "/BSOS/Accounting/Bill_Mngmt/showinvoice.php?rec="+rec+"&addr="+addr+"&client="+client;
	}
	else
	{
    	form.val.value = "client";
    	form.addr.value = form.addr.value;
        form.action = "emptimeshis.php";
        form.submit();
	}
}

function numSelected()
{
	var e = document.forms[0].elements;
	var bNone = true;
	var iFound = 0;
	for (var i=0; i < e.length; i++)
	{
    	if (e[i].name == "auids[]")
		{
			bNone = false;
			if (e[i].checked == true)
				iFound++;
		}
	}
	if (bNone)
		iFound = -1;
	return iFound;
}

function valSelected()
{
	var e = document.forms[0].elements;
	var bNone = true;
	var iVal = "";
	for (var i=0; i < e.length; i++)
	{
		if (e[i].name == "auids[]")
		{
			bNone = false;
			if (e[i].checked == true)
			{
				if(iVal=="")
					iVal=e[i].value;
				else
					iVal+=","+e[i].value;
			}
		}
	}
	if (bNone)
		iVal = "";
	return iVal;
}

function addRow(type)
{
	form=document.sheet;
	form.action="timesheet.php";
	val=form.rowcou.value;

	if(!CheckForFile(type))
	{
		if(type=="Delete")
		{
			if(doDelete(val))
				form.submit();
			else	
				form.action="savetime.php";
		}
		else
		{
			getTimeData(val);
			form.rowcou.value=parseInt(val)+1;
			form.submit();
		}
	}
}

function deleteMulti(type)
{
	form=document.sheet;
	form.action="multitimesheet.php";
	val=form.rowcou.value;
	if(type == 'Remove')
	{
		if(doRemove(val))
			form.submit();
	}
	
}
// New fucntion added to validate timesheet hours -- Vani
function isHoursNew(field,name)
{
	var str =field.value;
	if(isNaN(str) || str.substring(0,1)=="-" || str.substring(0,1)=="+")
	{
		alert("\n"+name+" field accepts numbers and decimals only. Enter a valid time value.");
		field.select();
		field.focus();
		return false;
	}
	/*if(str<0)
	{
		alert("You have entered Invalid Hours, Please enter a value between 0 and 24.");
		field.select();
		field.focus();
		return false;
	}*/
	return true;
}
function isHours(field,name)
{
    var str =field.value;
    str=str.replace(":",".");
    var dp=str.split(".");

	/*if(trim(field.value).length==0)
	{
		alert("The " + name + " field is empty. Please enter the " + name + ".");
		field.focus();
		return false;
	}

    if(!/^\d+(\.\d+)?$/.test(str))
    {

        alert("The Hours field contains Invalid characters. Please Re-enter the Hours.");
        field.select();
        field.focus();
        return false;
    }*/

    if(dp[1]>=60)
    {
        alert("The Hours or Minutes are larger than maximum allowed.");
        field.select();
        field.focus();
        return false;
    }
	if(str.substring(0,1)=="-")
	{
		alert("You have entered Invalid Hours, Please enter a value between 0 and 24.");
		field.select();
		field.focus();
		return false;
	}
	for (var i = 0; i < str.length; i++)
	{
		var ch = str.substring(i, i + 1);
		if( (ch < "0" || "9" < ch) && (ch !=".") )
		{
			alert("\nThe "+name+" field  accepts numbers and dots only.\n\nPlease re-enter your "+name+".");
			field.select();
			field.focus();
			return false;
		}
	}
	//commented to avoid hours restriction... By prasad
	/*if((str>24 || str<0) && (str!=""))
	{
        alert("You have entered Invalid Hours, Please enter a value between 0 and 24.");
        field.select();
        field.focus();
        return false;
    }*/
	return true;
}
function isOverHours(field,name)
{
    var str =field.value;
    str=str.replace(":",".");
    var dp=str.split(".");

    if(dp[1]>=60)
    {
        alert("The Hours or Minutes are larger than maximum allowed.");
        field.select();
        field.focus();
        return false;
    }
	if(str.substring(0,1)=="-")
	{
		alert("You have entered Invalid Hours, Please enter a value between 0 and 24.");
		field.select();
		field.focus();
		return false;
	}
	for (var i = 0; i < str.length; i++)
	{
		var ch = str.substring(i, i + 1);
		if( (ch < "0" || "9" < ch) && (ch !=".") )
		{
			alert("\nThe "+name+" field  accepts numbers and dots only.\n\nPlease re-enter your "+name+".");
			field.select();
			field.focus();
			return false;
		}
	}
	//commented to avoid hours restriction... By prasad
	/*if((str>24 || str<0) && (str!=""))
	{
        alert("You have entered Invalid Hours, Please enter a value between 0 and 24.");
        field.select();
        field.focus();
        return false;
    }*/
	return true;
}

/*
function getEmp()
{
	form=document.sheet;
	form.action="new_timesheet.php";
	form.rowcou.value = "";
	form.val.value = "";
	form.valtodate.value = "";
	form.submit();
}
*/

function getEmp()
{
	form=document.sheet;
	if (comingfor == 'firsttime') {
		form.action="new_timesheet.php?c=true";
	}
	else
	{
		form.action="new_timesheet.php";
	}
	//form.action="new_timesheet.php";
	form.submit();
}

function validate(act)
{
	form=document.sheet;
	var val_tot=0;
	var val=form.rowcou.value;
	val_tot=form.rowcou.value;
	
	var flag=true;
	var flag1=true;
	var arrTHPD = new Array();
	var arrIndex = new Array();
	var k = 0;
    
    var data="";
	var getdates="";
	var ttotal=0;
	var ttotal1=0;
	var ttotal2=0;
  	if(val == 0)
	{
		alert("Please add atleast one row to submit timesheet");
		return;
	}
	
	//*********** For selecting single assignment for entire week -- Start **********/
	var cnt_client = 0;
	var contvar = 0;
	var cliBox1_chk="";
	var cliBox1="";
	daterangehours = false;
	
	for(i=1;i<=val_tot;i++)
	{
		var lBox = document.getElementById('sdates_id'+i);
		var strchk = lBox.options[lBox.selectedIndex].value;
		var strrange = strchk.split('-range-');
		var strranglen = strrange.length;
		var thour = form.hours[i].value;

		if(strranglen == "2" && (thour != "") && (thour != "0.00"))
		{
			contvar = i;
			cliBox1 = document.getElementById('clientId'+i);
			cliBox1_chk = cliBox1.options[cliBox1.selectedIndex].value;
			daterangehours = true;
			dateRangeFilled = "YES";
		}
		else
		{
			cliBox1_chk = "";	
		}
			
		for(j=1;j<=val_tot;j++)
		{
			if(j == contvar)
				continue;
			
			var lBoxInner = document.getElementById('sdates_id'+j);
			var strchkInner = lBoxInner.options[lBoxInner.selectedIndex].value;
			var strrangeInner = strchkInner.split('-range-');
			var strranglenInner = strrangeInner.length;
			
			var cliBox = document.getElementById('clientId'+j);
			var clisel = cliBox.options[cliBox.selectedIndex].value;
			var thour1 = form.hours[j].value;
			
			if(strranglenInner != 2 && cliBox1_chk != "" && (thour1 != "") && (thour1 != "0.00"))
			{
				if(cliBox1_chk == clisel)
				{
					cnt_client++;
					break;
				}
			}
		}		
		if(strranglen == "2" && cnt_client>=1)
		{
			alert("You can't fill in time for the full date range and for individual days");
			return;
		}
		var lBoxValue = lBox.value;
		var tempTotal = 0.00;
		var temp_arr = form.servicedate.value.split("/");
		var strt_dt  = new Date(temp_arr[2],(temp_arr[0]-1),temp_arr[1]);
		
		var temp_arr = form.servicedateto.value.split("/");
		var end_dt  = new Date(temp_arr[2],(temp_arr[0]-1),temp_arr[1]);
		
		var tot_days = ((end_dt - strt_dt)/86400000)+1;
		var tot_hours_selected = tot_days * 24;
		for(k=1;k <= val_tot;k++)
		{
			var dateSelObj = document.getElementById('sdates_id'+k);
			var strchk1 = dateSelObj.value;
			var tempHoursVal = form.hours[k].value;
			
			var strrange1 = strchk1.split('-range-');
			var strranglen1 = strrange1.length;
			if(strranglen1 == "2")
			{
				var allRangeTotal = 0.00;
				for(c=1;c<=val_tot;c++)
				{
					var lBoxInner = document.getElementById('sdates_id'+c);
					var strchkInner = lBoxInner.options[lBoxInner.selectedIndex].value;
					var strrangeInner = strchkInner.split('-range-');
					var strranglenInner = strrangeInner.length;
					
					var thour1s = form.hours[c].value;
					if(strranglenInner == 2 && (thour1s != "") && (thour1s != "0.00"))
					{
						allRangeTotal = allRangeTotal + parseFloat(form.hours[c].value);
					}
				}
				if(allRangeTotal > tot_hours_selected)
				{
					alert("Total hours can't exceed "+tot_hours_selected+" in selected date range.");
					form.hours[k].focus();
					form.hours[k].select();
					return;
				}
			}
			if(dateSelObj.value == lBoxValue && tempHoursVal != "" && tempHoursVal != "0.00" && strranglen1 != 2)
			{
				tempTotal=parseFloat(tempHoursVal)+tempTotal;
			}
		}
		if(tempTotal > 24)
		{
			alert("You can't fill more than 24 hours in a day.");
			return;
		}
	}
	
	//*********** For selecting single assignment for entire week -- End **************/
	
	var servicedatefrom = form.servicedate.value;
	var servicedateto = form.servicedateto.value;
	
	var checking_from = form.checking_from.value;
	var checking_to = form.checking_to.value;
	
	/*if(servicedatefrom != checking_from || servicedateto != checking_to)
	{
		var chek = confirm( "Timesheet(s) will be submitted from "+checking_from+" to "+checking_to+".\n  Are you sure you want to submit the Timesheet?");
		
		if(chek == false)
			return;
		else
		{
			form.servicedate.value = checking_from;
			form.servicedateto.value = checking_to;
		}
	}*/
	form.servicedate.value = checking_from;
	form.servicedateto.value = checking_to;
	
	for(i=1;i<=val;i++)
	{
        if(CheckSpecChars(form.task[i]))
        {
            alert("The Task Details field do not accept  ^ |  characters.Please re-enter Task details.");
            form.task[i].focus();
            form.task[i].select();
            return;
        }
		//ttotal+=form.thours[i].value;
		//ttotal1+=form.othours[i].value;
		//ttotal2+=form.dbhours[i].value;
		ttotal+=form.hours[i].value;
            /*if(isHoursNew(form.thours[i],"Hours") && isHoursNew(form.othours[i],"OverTime Hours") && isHoursNew(form.dbhours[i],"Double Hours"))*/
		if(isHoursNew(form.hours[i],"Hours"))
		{
			if(data=="")
				data=form.sdates[i].options[form.sdates[i].selectedIndex].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i])+"|||"+getVal(form.class_type[i]);
			else
				data+="^"+form.sdates[i].options[form.sdates[i].selectedIndex].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i])+"|||"+getVal(form.class_type[i]);
			
			if(getVal(form.hours[i]) != "")
			{
				if(getdates == "")
					getdates = form.sdates[i].options[form.sdates[i].selectedIndex].value;
				else
					getdates += "^"+form.sdates[i].options[form.sdates[i].selectedIndex].value;
			}
			
			flag=true;
			flag1=true;
			arrDay = form.sdates[i].options[form.sdates[i].selectedIndex].value.split("-");
			day = arrDay[2];
		}
		else
		{
		  flag=false;
		  break;
		}
   }
   form.getdates.value = getdates;

	if(flag)
	{
        var totRHours=document.getElementById("totalhr").innerHTML;//deepak
        //var totOHours=document.getElementById("othour").innerHTML;//deepak
        //var totDHours=document.getElementById("dbhour").innerHTML;//deepak

        if( (totRHours==0 || totRHours=="0.00"))
        {
            alert("You can't submit a timesheet without hours.");
            
        }
        else
        {
			form.aa.value=act;
			form.timedata.value=data;
			
			if(form.module.value == 'MyProfile')
				daterangehours = false;
			if(daterangehours)
			{
				PopMsgHeadArr['single_timesheet']="Create Timesheets";
				PopMsgFLineArr['single_timesheet']="Hours entered in the first row for a range of dates cannot be used for generating gross pay. Only hours in the individual daily columns are used to generate gross pay.";
				PopMsgQueArr['single_timesheet']="Click <b>OK</b> to submit hours for a date range.<br>Click <b>Cancel</b> to go back to the screen.";
				PopMsgSLineArr['single_timesheet']="";
				PopMsgExtMsgArr['single_timesheet']="";
				display_Dynmic_Message('single_timesheet','cancel','ok','','displaySingleTimeGridAlert');
			}
			else
			{
				//Call this for already submitted validation for date range..
				timesheetSubmitCheckAlert(form);
				
				var empUsernames = form.empnames.value;
				var moduleName = form.module.value;
				var content = "rtype=getTimesheetStatus&multiple=NO&moduleName="+moduleName+"&dateRangeFilled="+dateRangeFilled+"&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames+"&getdates="+getdates;
				var url = "/BSOS/Include/getAsgn.php";
				DynCls_Ajax_result(url,'rtype',content,"getValidateTimesheet('single')");
				return;
			
				//form.submit();
			}
        }
	}
}

function displaySingleTimeGridAlert(retstatus)
{
     switch(retstatus)
     {
    	case 'cancel':	break;
    	case 'ok': 
		submitMultipleTime();
		break;
     }
}

function submitMultipleTime()
{
	form=document.sheet;
	
	//Call this for already submitted validation for date range..
	timesheetSubmitCheckAlert(form);
	
	var empUsernames = form.empnames.value;
	var moduleName = form.module.value;
	var checking_from = form.checking_from.value;
	var checking_to = form.checking_to.value;
	var getdates = form.getdates.value;
	var content = "rtype=getTimesheetStatus&multiple=NO&moduleName="+moduleName+"&dateRangeFilled="+dateRangeFilled+"&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames+"&getdates="+getdates;
	var url = "/BSOS/Include/getAsgn.php";
	DynCls_Ajax_result(url,'rtype',content,"getValidateTimesheet('single')");
	return;
				
	/*if(form.module.value == 'MyProfile')
		form.action = '../../../BSOS/MyProfile/Timesheet/savetime.php';
	else if(form.module.value == 'Client')
		form.action = '/BSOS/Client/savetime.php';
	form.submit();*/
}

function doDelete(val)
{
	numAddrs = numSelected();
	valAddrs = valSelected();
	if (numAddrs < 0)
	{
		alert("There are no entries to delete.");
		return false;
	}
	else if (! numAddrs)
	{
		alert("You have to select atleast one Timesheet entry to delete from the Available List.");
		return false;
	}
	else if (numAddrs == val)
	{
		alert("Your Timesheets must have atleast one entry. You can't delete all the entries.");
		return false;
	}
	else
	{
		getActiveEntries(valAddrs);		
		return true;
	}
}

function doRemove(val)
{
	numAddrs = numSelected();
	valAddrs = valSelected();
	if (numAddrs < 0)
	{
		alert("There are no entries to remove.");
		return false;
	}
	else if (! numAddrs)
	{
		alert("You have to select atleast one Timesheet entry to remove from the Available List.");
		return false;
	}
	else
	{
		removeSelectedRows(valAddrs);
		return true;
	}
}

function getTimeData(val)
{
	form=document.sheet;
	var data="";
	for(i=1;i<=val;i++)
	{
        try
        {
		 if(data=="")
			data=form.sdates[i].options[form.sdates[i].selectedIndex].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i])+"|||"+getVal(form.class_type[i]);
		else
			data+="^"+form.sdates[i].options[form.sdates[i].selectedIndex].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i])+"|||"+getVal(form.class_type[i]);	
			
    	}
    	catch(e)
    	{
			return;
        }
	}
	form.timedata.value=data;
}


function removeSelectedRows(val)
{

var colcount = document.getElementById('ratecount').value;

	form=document.sheet;
	var k=0;
	var data="";
	var total=form.rowcou.value;
	var val1=val.split(",");
	var flag=false;
	var temp = "";
	for(i=1;i<=total;i++)
	{
		for(j=0;j<val1.length;j++)
		{
			temp = val1[j].split("_") 
			if(temp[0] == i)
			{
				flag=false;
				break;
			}
			else
			{
				flag=true;
			}
		}
		if(flag)
		{
            try
            {
				var hourstr = '';
				var billstr = '';
				
				for(var x=0; x <=colcount-1; x++)
				{
					if(hourstr == '')
					{						
						if (x == 0) {
							hourstr = document.getElementById('daily_rate_'+x+'_'+i).value;;
						}else{
							hourstr = ',' + document.getElementById('daily_rate_'+x+'_'+i).value;
						}
					}
					else
					{
						hourstr += ","+document.getElementById('daily_rate_'+x+'_'+i).value;
					}
					
					if(billstr == '')
					{
						if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
						{
							billstr = 'Y';
						}
						else
						{
							billstr = 'N';
						}
						
					}
					else
					{
						if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
						{
							billstr += ',Y';
						}
						else
						{
							billstr += ',N';
						}
					}
				}
			
			firstVal = form.empusername[i].value;
			if(data=="")
					data=firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
				else
					data+="^"+firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
			k++;
        	}
            catch(e)
            {
            return;
            }
		}
	}
	
	if(form.empnames.value != '')
			{
				if(data == "")
					data = form.empnames.value+"|||||||||";
				else
					data = form.empnames.value+"|||||||||"+"^"+data;
			}
			
		form.rowcou.value=k;
	    form.timedata.value=data;
		form.submit();	
}

function getActiveEntries(val)
{
	form=document.sheet;

	var k=0;
	var data="";
	var total=form.rowcou.value;
	var val1=val.split(",");
	var flag=false;

	for(j=1;j<=total;j++)
	{
		for(i=0;i<val1.length;i++)
		{
			if(val1[i]==j)
			{
				flag=false;
				break;
			}
			else
			{
				flag=true;
			}
		}
		if(flag)
		{
            try
            {
			if(data=="")
				data=form.sdates[j].options[form.sdates[j].selectedIndex].value+"|"+form.client[j].options[form.client[j].selectedIndex].value+"|"+getVal(form.task[j])+"|"+getVal(form.hourstype[j])+"|"+getBilVal(form.billable[j])+"|"+getVal(form.hours[j])+"|||"+getVal(form.class_type[j]);
			else
				data+="^"+form.sdates[j].options[form.sdates[j].selectedIndex].value+"|"+form.client[j].options[form.client[j].selectedIndex].value+"|"+getVal(form.task[j])+"|"+getVal(form.hourstype[j])+"|"+getBilVal(form.billable[j])+"|"+getVal(form.hours[j])+"|||"+getVal(form.class_type[j]);	
			k++;
        	}
            catch(e)
            {
            return;
            }
		}

	}
	form.rowcou.value=k;
	form.timedata.value=data;	
}

function getBilVal(obj)
{
	if(obj.checked)
		return "Yes";
	else
		return "no";
	return "Yes";
}

function getVal(obj)
{
	if(obj.value=="")
		return "";
	else
		return obj.value;
}

function clearAll()
{
	var e = document.forms[0].elements;
	for (var i=0; i < e.length; i++)
		if (e[i].name == "auids[]")
			e[i].checked = false;
}

function checkAll()
{
	var e = document.forms[0].elements;
	for (var i=0; i < e.length; i++)
		if (e[i].name == "auids[]")
		{
			if(e[i].disabled!=true)
				e[i].checked = true;
		}
}

function chke(e)
{
	if(!e)
	var e = document.forms[0].chk;
	if(e.checked==true)
		checkAll();
	else
		clearAll();
}



function getBillT(j)
{
	form=document.sheet;

	var earn=0;
	j=parseInt(j)+1;

	form.billable[j].disabled = false;
	var ass=form.client[j].options[form.client[j].selectedIndex].value;	
	var hrconsnoClient = form.client[j].options[form.client[j].selectedIndex].id;	
	
	if(hrconsnoClient != '')
	{
		var hrconClientArr = hrconsnoClient.split('-');	
		var clientid = hrconClientArr[1];		
	}
	else
		var clientid = "";	
		
	var billable = form.hourstype[j].options[form.hourstype[j].selectedIndex].id;
	var billableVal = form.hourstype[j].options[form.hourstype[j].selectedIndex].value;
	
	if(ass!="AS" && ass!="OB" && ass!="OV")
		earn=ass.search("earn");
		
	if(((ass!="AS" && ass!="OB" && ass!="OV" && earn<0) || ((ass=="AS" && (clientid != '' && clientid != 0))|| (ass=="OB" && (clientid != '' && clientid != 0)) ||  (ass=="OV" && (clientid != '' && clientid != 0)) || (earn>0 && (clientid != '' && clientid != 0)))))
	{		
		if(billable == 'Y')
			form.billable[j].checked=true;
		else
			form.billable[j].checked=false;
	}
	else if(clientid == '' || clientid == 0)
	{		
		if(ass=="AS" || ass=="OB" ||  ass=="OV" || earn>0)
		{
			form.billable[j].checked=false;
			form.billable[j].disabled = true;
		}
	}
}

function getMultipleRate_backup(j)
{
	form=document.sheet;
	r = j;
	j=parseInt(j)+1;
	
	var hrconsnoClient = form.client[j].options[form.client[j].selectedIndex].id;
	if(hrconsnoClient != '')
	{
		var hrconClientArr = hrconsnoClient.split('-');	
		var hrconsno = hrconClientArr[0];		
	}
	else
		var hrconsno = "";	
	
	var content = "hrconsno="+hrconsno;
	
	var url = "/BSOS/Accounting/Time_Mngmt/multipleRateChange.php";
	
	DynCls_Ajax_result(url,'rtype',content,"getResponseMultipleRates('"+r+"')");
	
}


function getMultipleRate(asgnrowid)
{
	var empacc = $("#empusername_"+asgnrowid).val();
	var asgnval = $("#daily_assignment_"+asgnrowid).children(":selected").attr("id");
	var asgnid = asgnval.split("-");
	var url = "/include/loadtimedata.php?empacc="+empacc+"&asgn="+asgnid[0]+"&rowid="+asgnrowid+"&mod=asgn&mod_mul=multi_asgn";
	$.get(url, function( data ) {
		var dataArr = data.split("|");
		var ratecount = dataArr.length;
		
		for(i=0; i<ratecount; i++)
		{
			var dataArrinternal = dataArr[i].split(",");
			if(dataArrinternal[0] == '(earn)PTO'){
				dataArrinternal[0] = 'PTO';
			}
			
			if(dataArrinternal[1] == 'Y')
			{	
				$("#MainTable input[id="+dataArrinternal[0]+"]").attr('disabled', false);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', false);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val('');
			}
			else
			{
				$("#MainTable input[id="+dataArrinternal[0]+"]").attr('disabled', true);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', true);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('checked', false);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val('');
			}
			if(dataArrinternal[3] == 'Y')
			{	
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', false);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('checked', true);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val('');
			}
			else
			{
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', true);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('checked', false);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val('');
			}
			var pay = (dataArrinternal[5]=='')?'0.00':dataArrinternal[5];
			var bill = (dataArrinternal[7]=='')?'0.00':dataArrinternal[7];
			$("#MainTable span[id="+dataArrinternal[4]+"]").html(pay);
			$("#MainTable span[id="+dataArrinternal[6]+"]").html(bill);
		}
		get_cur_placeholders("daily_assignment_"+asgnrowid,asgnrowid);
		});
	
	var url = "/include/loadtimedata.php?empacc="+empacc+"&asgn="+asgnid[0]+"&rowid="+asgnrowid+"&mod=editasgnlink";
	$.get(url, function( data ) {
		if (data.indexOf('earn') > -1) {
			$(".daily_rate_pay_link_"+asgnrowid).html('');
		}
		else
		{
			$(".daily_rate_pay_link_"+asgnrowid).html(data);
		}
		
	});
	
	setRatesData();
}

function getMultipleRateTimesheet(asgnid, rowid)
{
	var selectedAsgnId = $("#daily_assignment_"+rowid).children(":selected").attr("id");
	var url = "/include/loadtimedata.php?asgn="+asgnid+"&rowid="+rowid+"&mod=asgntimesheet";
	$.get(url, function( data ) {
		var dataArr = data.split("|");
		var newasgnid = dataArr.pop();
		var ratecount = dataArr.length;
		
		for(i=0; i<ratecount-1; i++)
		{
			var dataArrinternal = dataArr[i].split(",");

			if(dataArrinternal[1] == 'Y')
			{	
				$("#MainTable input[id="+dataArrinternal[0]+"]").attr('readonly', false);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', false);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val('');
			}
			else
			{
				$("#MainTable input[id="+dataArrinternal[0]+"]").attr('readonly', true);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', true);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('checked', false);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val('');
			}
			if(dataArrinternal[3] == 'Y')
			{	
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', false);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('checked', true);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val('');
			}
			else
			{
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('disabled', true);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr('checked', false);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val('');
			}
			var pay = (dataArrinternal[5]=='')?'0.00':dataArrinternal[5];
			var bill = (dataArrinternal[7]=='')?'0.00':dataArrinternal[7];
			$("#MainTable span[id="+dataArrinternal[4]+"]").html(pay);
			$("#MainTable span[id="+dataArrinternal[6]+"]").html(bill);
		}
		var selectedAsgnIdsplit = selectedAsgnId.split("-");
		selectedAsgnIdsplit[0] = newasgnid;
		var selectedAsgnIdsplitjoin = selectedAsgnIdsplit.join("-");
		if (selectedAsgnId.indexOf('earn') == -1)
		{
			$("#daily_assignment_"+rowid).children(":selected").attr("id", selectedAsgnIdsplitjoin);
		}
				
	});
}

function getMultipleRateTimesheetAssgn(asgnid, rowid)
{
	var selectedAsgnId = $("#daily_assignment_"+rowid).children(":selected").attr("id");
	var url = "/include/loadtimedata.php?asgn="+asgnid+"&rowid="+rowid+"&mod=asgntimesheet";
	$.get(url, function( data ) {
		var dataArr = data.split("|");
		var newasgnid = dataArr.pop();
		var ratecount = dataArr.length;
		
		for(i=0; i<ratecount-1; i++)
		{
			var dataArrinternal = dataArr[i].split(",");

			var pay = (dataArrinternal[5]=='')?'0.00':dataArrinternal[5];
			var bill = (dataArrinternal[7]=='')?'0.00':dataArrinternal[7];
			$("#MainTable span[id="+dataArrinternal[4]+"]").html(pay);
			$("#MainTable span[id="+dataArrinternal[6]+"]").html(bill);
		}
		var selectedAsgnIdsplit = selectedAsgnId.split("-");
		selectedAsgnIdsplit[0] = newasgnid;
		var selectedAsgnIdsplitjoin = selectedAsgnIdsplit.join("-");
		if (selectedAsgnId.indexOf('earn') == -1)
		{
			$("#daily_assignment_"+rowid).children(":selected").attr("id", selectedAsgnIdsplitjoin);
		}
				
	});
}

function getResponseMultipleRates(r)
{
	form=document.sheet;
	j=parseInt(r)+1;
	
	form.hourstype[j].options.length = null;
	var classObj = form.class_type[j];
	var optoin_Array = DynCls_Ajx_responseTxt.split("^");
	var classSelVal = "0";
	for(i = 0; i< optoin_Array.length; i++)
	{	
		optionvalues = optoin_Array[i].split("|");		
		form.hourstype[j].options[i] = new Option(optionvalues[1], optionvalues[0]);
		form.hourstype[j].options[i].id = optionvalues[2];
		classSelVal = optionvalues[3];
	}
	if(!document.getElementById("multi_edit"))
	{
		if(classObj.type != "hidden")
		{
			var classLen = classObj.options.length;
			for (k=0; k < classLen; k++)
			{
				if(classObj.options[k].value == classSelVal)
					classObj.options[k].selected=true;
			}
		}
		else
		{
			classObj.value = classSelVal;
		}
	}
	getBillT(r);
}


function CheckForFile(strOperation)
{
	form=document.sheet;
	var strData = "";
	
	if(strOperation == "Delete")
		strData = "Delete";
	else
		strData = "Add";
	
	if(form.timefile.value == "")
		return false;
	else
		return !(confirm("If you " + strData + " row(s), you need to upload the file again. Do you want to continue?"));
}

// For Time sheets reminders..
function doSend()
{
    window.location.href = "doreminder.php";
}

function doSendReminder()
{
	var form=document.myname;
	form.tnotes.value = trimAll(form.tnotes.value)
	if(form.tnotes.value!="")
	{
        form.submit();
	}
	else
	{
		alert("The Reminder Notes field is empty. Please enter the Reminder Notes.");
		form.tnotes.focus();
	}
}

function doSetup()
{
	document.location.href = "timesetup.php";
}

function RemCancel()
{
	document.location.href = "empfaxhis.php";
}

function chklen1(field,name)
{
	var a=field.value.length;
	if((a<3))
	{
		alert(name+"  is minimum of 3 characters");
		field.focus();
		field.select();
		return false;
	}
	return true;
}

function chklen2(field,name)
{
	var a=field.value.length;
	if((a<4))
	{
		alert(name+"  is minimum of 4 characters");
		field.focus();
		field.select();
		return false;
	}
	return true;
}

function isNotEmpty(field, name)
{

	var str=field.value;
	if(str=="")
	{
		alert("The " + name + " field is empty. Please enter the " + name + ".");
		field.focus();
		return false;
	}
	return true;
}

function isNumber(field,name)
{
	var str =field.value;
	for(var i=0;i<str.length;i++)
	{
		if((str.substring(i,i+1)<"0") || (str.substring(i,i+1)>"9"))
		{
			alert("The "+name+" accepts numbers only.\n\nPlease re-enter your "+name+".");
			field.select();
			field.focus();
			return false;
		}
	}
	return true;
}

function doSave()
{
	form=document.setup;
//	if( isNotEmpty(form.fax,"Fax Number") && isNotEmpty(form.fax1,"Fax Number") && isNotEmpty(form.fax2,"Fax Number") && isNumber(form.fax,"Fax Number") && isNumber(form.fax1,"Fax Number") && isNumber(form.fax2,"Fax Number") && chklen1(form.fax,"Fax Number") && chklen1(form.fax1,"Fa Numberx") && chklen2(form.fax2,"Fax Number"))
	form.val.value="old";
	form.submit();
}

function doCancel()
{
	document.location.href="doreminder.php";
}
function doPrint(id,invoice)
{
	var v_heigth = window.screen.availHeight-50;
	var v_width  = window.screen.availWidth-10;
	
	
	//var parchkedrows = "<?php echo $tsno."|".$cun."|".$clival; ?>";
	path = "printtimesheet.php?addr1="+id+"&invoice="+invoice;
	remote=window.open(path,"timesheets","width="+v_width+"px,height="+v_heigth+"px,statusbar=yes,menubar=no,scrollbars=yes,left=0,top=0,dependent=yes,resizable=yes");
	remote.focus();
}
function updateAssignList(selDate)
{
	form=document.sheet;
	form.action="timesheet.php";
	val=form.rowcou.value;
	getTimeData(val);
	form.submit();
}
// Function to add employee in creating multiple timesheets.
	function addEmployee()
	{
		form = document.sheet;
		if( window.location.href.indexOf('/BSOS/Include/edittimemulti.php')>0 )
		{
			form.action = "/BSOS/Include/edittimemulti.php";
			if(form.timefile.value != "")
			{
				var fileConfirm = (confirm("If you add row(s), you need to upload the file again. Do you want to continue?"));	
				if(fileConfirm)
					form.timefile.value == "";
				else
					return;
			}
			form.newrowcou.value=form.rowcou.value;
		}
		else
			form.action = "multitimesheet.php";
		val = form.rowcou.value;		
			
		var data="";
		for(i=1;i<=val;i++)
		{
			if(window.location.href.indexOf('/BSOS/Include/edittimemulti.php')>0)
				firstVal = form.sdates[i].value;
			else
				firstVal = form.empusername[i].value;
			if(data=="")
				data=firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
			else
				data+="^"+firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);			
			
		}
		if(window.location.href.indexOf('/BSOS/Include/edittimemulti.php')>0)
		{
			if(data == "")
				data = "||||||||||||||";
			else
				data = data+"^"+"||||||||||||||";
		}
		else
		{
			if(form.empnames.value != '')
			{
				if(data == "")
					data = form.empnames.value+"|||||||||";
				else
					data = form.empnames.value+"|||||||||"+"^"+data;
			}
			else
			{
				alert('Select an employee to submit Timesheet');
				return;
			}
		}
		form.timedata.value=data;
		form.rowcou.value=parseInt(val)+1;
		form.submit();		
	}
	
	function addNewEmployee()
	{
		form = document.sheet;
		
		if(form.empnames.value == "")
		{
			alert('Select an employee to submit Timesheet');
			return;
		}
		else
		{
			// Commented not to show popup //////////////
			//var e = document.getElementsByName('auids[]');
			//var totalchkbox = e.length;
			//var checking_from = form.checking_from.value;
			//var checking_to = form.checking_to.value;
			//var getdates = checking_from+'-range-'+checking_to;
			//var empUsernames = form.empnames.value;
			//
			//var content = "rtype=getTimesheetStatus&multiple=No&moduleName=Accounting&dateRangeFilled=NO&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames+"&getdates="+getdates;
			//var url = "/BSOS/Include/getAsgn.php";
			//DynCls_Ajax_result(url,'rtype',content,"addNewEmployeeAlert()");
			
			getNewEmployeeAlert('1','');
		}
	}
	
	function addNewEmployeeAlert()
	{
		form=document.sheet;
		var timesheetStatusTxt = DynCls_Ajx_responseTxt;
		if(timesheetStatusTxt != 0)
			{
				var rspsArr = timesheetStatusTxt.split("|");
				var alMsg ='<tr><td class="alert-time-msg"><b>Timesheet exists for this employee for the below dates.</b></td></tr><tr><td class="alert-time-msg"><div style="height:auto; overflow:auto">'+rspsArr[0]+'</div></td></tr>';
				var htmDisplay = '<div style="height:25px; background-color:#00B9F2; font-family:Tahoma, Helvetica, sans-serif; font-size:small; font-weight:bold; color:Captiontext; vertical-align:middle; text-align:left;"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr valign="middle"><td id="captionTd" style="width:96%; padding:5px; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; color:#000000;" valign="middle"><b>Confirmation</b></td></tr></table></div><table style="width:100%; height:95%; background-color:#FFFFFF;" border="0">'+alMsg+'<tr><td class="alert-time-msg">Click on <b>Yes</b> to continue creating another timesheet for the same date range or <b>Cancel</b> to return to screen.</td></tr><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmYes" id="btnConfirmYes" value="Yes" onClick="javascript: getNewEmployeeAlert(\'1\');"  class="time-alert-button" /> &nbsp; <input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getNewEmployeeAlert(\'-1\');" class="time-alert-button" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></table>';
				callPopupWindow(htmDisplay);
				return;
			}else
			{
				getNewEmployeeAlert('1','');
			}				
	}
	
	function getNewEmployeeAlert(status)
	{
	var colcount = document.getElementById('ratecount').value;
		var v_heigth = 300;
		var v_width  = 600;
		var top1=(window.screen.availHeight-v_heigth)/2;
		var left1=(window.screen.availWidth-v_width)/2;
		form = document.sheet;
		form.action = "multitimesheet.php";
		val = form.rowcou.value;
		var data = "";
				
		switch (status){
			case '1':	
					var emp = document.getElementById("empnames");
					var empUsernames = emp.options[emp.selectedIndex].value;
					
					//alert(empUsernames);
							
					var firstAsgnStr = $( "#empnames option:selected" ).text();
					
					var firstAsgnStrSplit = firstAsgnStr.split('-');
					if (firstAsgnStrSplit.length > 2)
					{
						var firstAsgn = firstAsgnStrSplit.pop();
					}
					else
					{
						var firstAsgn = '';
					}
					
					
					for(i=1;i<=val;i++)
					{
						var billstr = '';
						var hourstr = '';
						
						for(var x=0; x <=colcount-1; x++)
						{
							if(hourstr == '')
							{								
								if (x == 0) {
									hourstr = document.getElementById('daily_rate_'+x+'_'+i).value;;
								}else{
									hourstr = ',' + document.getElementById('daily_rate_'+x+'_'+i).value;
								}
							}
							else
							{
								hourstr += ","+document.getElementById('daily_rate_'+x+'_'+i).value;
							}
							
							if(billstr == '')
							{
								if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
								{
									billstr = 'Y';
								}
								else
								{
									billstr = 'N';
								}
								
							}
							else
							{
								if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
								{
									billstr += ',Y';
								}
								else
								{
									billstr += ',N';
								}
							}
						}
						/* firstVal = form.empusername[i].value;
						if(data=="")
						data=firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
						else
						data+="^"+firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);	 */

						firstVal = form.empusername[i].value;
						if(data=="")
						{
						data=firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
						}
						else
						{
						data+="^"+firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);	
						}
					}
					if(form.empnames.value != '')
					{
						if(data == "")
						//data = form.empnames.value+"|"+firstAsgn.split(' ').join(' ')+"||||||||";
						data = form.empnames.value+"|"+firstAsgn.replace(/^\s+|\s+$/g,'')+"||||||||";
						else
						//data = form.empnames.value+"|"+firstAsgn.split(' ').join(' ')+"|||||||||"+"^"+data;
						data = form.empnames.value+"|"+firstAsgn.replace(/^\s+|\s+$/g,'')+"|||||||||"+"^"+data;
					}
					else
					{
						alert('Select an employee to submit Timesheet');
						return;
					}
					
					form.timedata.value=data;
					form.rowcou.value=parseInt(val)+1;
					form.submit();					
			break;
			case '-1':
			break;
		}
		document.getElementById("dynsndiv").style.display = "none";
		document.getElementById("SaveAlert").style.display = "none";
		
	}	
	
	function getNewEmployeeAlertTS(status)
	{
	var colcount = document.getElementById('ratecount').value;
		var v_heigth = 300;
		var v_width  = 600;
		var top1=(window.screen.availHeight-v_heigth)/2;
		var left1=(window.screen.availWidth-v_width)/2;
		form = document.sheet;
		form.action = "multitimesheet.php";
		val = form.rowcou.value;
		var data = "";
				
		switch (status){
			case '1':	
	
					var empUsernames = form.empnames.value;
					var firstAsgnStr = form.empnames.options[form.empnames.selectedIndex].text;
					var firstAsgnStrSplit = firstAsgnStr.split('-');
					if (firstAsgnStrSplit.length > 2)
					{
						var firstAsgn = firstAsgnStrSplit.pop();
					}
					else
					{
						var firstAsgn = '';
					}
					
					
					for(i=1;i<=val;i++)
					{
						var billstr = '';
						var hourstr = '';
						
						for(var x=0; x <=colcount-1; x++)
						{
							if(hourstr == '')
							{								
								if (x == 0) {
									hourstr = document.getElementById('daily_rate_'+x+'_'+i).value;;
								}else{
									hourstr = ',' + document.getElementById('daily_rate_'+x+'_'+i).value;
								}
							}
							else
							{
								hourstr += ","+document.getElementById('daily_rate_'+x+'_'+i).value;
							}
							
							if(billstr == '')
							{
								if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
								{
									billstr = 'Y';
								}
								else
								{
									billstr = 'N';
								}
								
							}
							else
							{
								if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
								{
									billstr += ',Y';
								}
								else
								{
									billstr += ',N';
								}
							}
						}
						/* firstVal = form.empusername[i].value;
						if(data=="")
						data=firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
						else
						data+="^"+firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);	 */

						firstVal = form.empusername[i].value;
						if(data=="")
						{
						data=firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
						}
						else
						{
						data+="^"+firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);	
						}
					}
					
					form.timedata.value=data;
					form.rowcou.value=parseInt(val)+1;
					form.submit();					
			break;
			case '-1':
			break;
		}
		document.getElementById("dynsndiv").style.display = "none";
		document.getElementById("SaveAlert").style.display = "none";
		
	}
	
	// Function for submit multiple timesheets
	function validateMulti(act)
	{
		
		var ele = document.getElementById("timesubmit");
		elehref = ele.href;
		ele.href = 'javascript:void(0)';
		
		form=document.sheet;
		var val_tot=0;
		val=form.rowcou.value;	
		val_tot=form.rowcou.value;
		var timeSubmitFlag = 0;
		
		var temp_arr = form.servicedatefrom.value.split("/");
		var strt_dt  = new Date(temp_arr[2],(temp_arr[0]-1),temp_arr[1]);
		
		var temp_arr = form.servicedateto.value.split("/");
		var end_dt  = new Date(temp_arr[2],(temp_arr[0]-1),temp_arr[1]);
		
		var tot_days = ((end_dt - strt_dt)/86400000)+1;
		var tot_hours_selected = tot_days * 24;
		if(val <= 0)
		{
			alert("There are no employees selected to submit the timesheet");
			ele.href = elehref;
			return;
		}
		
		if(window.location.href.indexOf("BSOS/Accounting/Time_Mngmt/multitimesheet.php") < 0)
		{
			var cnt_client = 0;
			var contvar = 0;
			var cliBox1_chk="";
			var cliBox1="";
			//this variable for submit condition...
			timeSubmitFlag = document.getElementById("timeSubmitFlag").value;
			for(i=1;i<=val_tot;i++)
			{
				var lBox = document.getElementById('sdates_id'+i);
				var strchk = lBox.options[lBox.selectedIndex].value;
				var strrange = strchk.split('-');
				var strranglen = strrange.length;
				var thour = form.hours[i].value;

				if(strranglen == "2" && (thour != "") && (thour != "0.00"))
				{
					contvar = i;
					cliBox1 = document.getElementById('clientId'+i);
					cliBox1_chk = cliBox1.options[cliBox1.selectedIndex].value;
				}
				else
				{
					cliBox1_chk = "";	
				}
				
				for(j=1;j<=val_tot;j++)
				{
					if(j == contvar)
						continue;
				
					if(document.getElementById('sdates_id'+j))
					{
						var lBoxInner = document.getElementById('sdates_id'+j);
						var strchkInner = lBoxInner.options[lBoxInner.selectedIndex].value;
						var strrangeInner = strchkInner.split('-');
						var strranglenInner = strrangeInner.length;
					}
					else
					{
						var strranglenInner = 1;
					}
			
					var cliBox = document.getElementById('clientId'+j);
					var clisel = cliBox.options[cliBox.selectedIndex].value;
					var thour1 = form.hours[j].value;

					if(strranglenInner != 2 && cliBox1_chk != "" && (thour1 != "") && (thour1 != "0.00"))
					{
						if(cliBox1_chk == clisel)
						{
							cnt_client++;
							break;
						}
					}
				}	
			
				if(strranglen == "2" && cnt_client>=1)
				{
					alert("You can't fill in time for the full date range and for individual days");
					ele.href = elehref;
					return;
				}
			}
		}

		var k = 0;
		
		var data="";
		var ttotal=0;
		var ttotal1=0;
		var ttotal2=0;
		var servicedatefrom = form.servicedatefrom.value;
		var servicedateto = form.servicedateto.value;
		
		var checking_from = form.checking_from.value;
		var checking_to = form.checking_to.value;
		
		form.servicedatefrom.value = checking_from;
		form.servicedateto.value = checking_to;
		
		var checknumRowsTS="YES";
		var totEmpSel=0;
		var totEmpSelTS=0;
		var totEmpSelTS_UOM=0;
		var empUsernames = "";
		for(i=1;i<=val;i++)
		{
			if(CheckSpecChars(form.task[i]))
			{
				alert("The Task Details field do not accept  ^ |  characters.Please re-enter Task details.");
				form.task[i].focus();
				form.task[i].select();
				ele.href = elehref;
				return;
			}
			if(document.getElementById('sdates_id'+i))
			{
				var dateSelObj = document.getElementById('sdates_id'+i);
				var strchk1 = dateSelObj.value;
			}
			else
			{
				var strchk1 = "";
			}
			var tempHoursVal = form.hours[i].value;
			
			var strrange1 = strchk1.split('-');
			var strranglen1 = strrange1.length;
			
			if(strranglen1 == "2")
			{
				var allRangeTotal = 0.00;
				for(c=1;c<=val_tot;c++)
				{
					var lBoxInner = document.getElementById('sdates_id'+c);
					var strchkInner = lBoxInner.options[lBoxInner.selectedIndex].value;
					var strrangeInner = strchkInner.split('-');
					var strranglenInner = strrangeInner.length;
					
					var thour1s = form.hours[c].value;
					if(strranglenInner == 2 && (thour1s != "") && (thour1s != "0.00"))
					{
						allRangeTotal = allRangeTotal + parseFloat(form.hours[c].value);
					}
				}
				if(allRangeTotal > tot_hours_selected)
				{
					alert("Total hours can't exceed "+tot_hours_selected+" in selected date range.");
					form.hours[k].focus();
					form.hours[k].select();
					ele.href = elehref;
					return;
				}
			}
			else
			{
				if(form.hours[i].value > tot_hours_selected)
				{
					alert("Total hours can't exceed "+tot_hours_selected+" in selected date range.");
					form.hours[i].focus();
					form.hours[i].select();
					ele.href = elehref;
					return;
				}
			}
			
			if(isHoursNew(form.hours[i],"Hours"))
			{
				
		                if( (form.hours[i].value!=0 && form.hours[i].value!="0.00" && form.hours[i].value!="" && form.hours[i].value!="." && form.hours[i].value != ":"))
		                {
		                    totEmpSelTS++;
		                }
				
		                totEmpSel++
                
				if(window.location.href.indexOf('/BSOS/Include/edittimemulti.php')>0)
				{
					var firstVal = form.sdates[i].value;
					var checknumRowsTS="NO";
				}
				else
					var firstVal = form.empusername[i].value;
				if(data=="")
				{
					data=firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i])+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
					empUsernames = ""+firstVal+"";
				}
				else
				{
					data+="^"+firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i])+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i])+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
					empUsernames+= "^"+firstVal+"";;
				}
			}		
			else{
				totEmpSel++;
		   		return;
			}
				var grandTotal = document.getElementById('grandTotal_'+i).value;
				if( (grandTotal!=0 && grandTotal!="0.00" && grandTotal!="" && grandTotal!="." && grandTotal != ":"))
				{
					totEmpSelTS_UOM++;
				}	
		  	 }
  
	if(totEmpSelTS_UOM==0)
	{
            totEmpSelTS_UOM=0;
            totEmpSel=0;
            
            alert("You can't submit a timesheet without entering any value.");
	    ele.href = elehref;
        }
        else if(totEmpSelTS_UOM != totEmpSel && checknumRowsTS=="YES")
        {
            var chkstatus=confirm( "Hours/Days*/Miles*/Units* are entered for "+totEmpSelTS_UOM+" out of "+totEmpSel+" employees.           \nTimesheets will only be created for those employees that have hours/days*/miles*/units* entered.");
            totEmpSelTS_UOM=0;
            totEmpSel=0;
            
            if(chkstatus==false)
            {
		ele.href = elehref;
                return;
            }
            else
            {
                form.aa.value=act;
                form.timedata.value=data;
                form.submit();
            }
        }
		else
		{
         	totEmpSelTS=0;
         	totEmpSelTS_UOM=0;
            totEmpSel=0;

            form.aa.value=act;
			form.timedata.value=data;
    		form.submit();
        }
	}
	
	function getvalidateTimeRange()
	{
		form=document.sheet;
		var e = document.getElementsByName('auids[]');
		var totalchkbox = e.length;
		var checking_from = form.servicedatefrom.value;
		var checking_to = form.servicedateto.value;

		var empUsernames = '';
		
		for(var i=0; i<totalchkbox; i++)
		{
			val = e[i].value;
			var chkid = val.split('_');
			if(i==0)
				empUsernames = chkid[1];
			else
				empUsernames += "^"+chkid[1]+"";
		}
			var content = "rtype=getTimesheetStatus&multiple=YES&moduleName=Accounting&dateRangeFilled=NO&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames;
			var url = "/BSOS/Include/getAsgn.php";
			DynCls_Ajax_result(url,'rtype',content,"getValidateTimesheetAlert()");
	}
	
	function getValidateTimesheetAlert()
	{
		form=document.sheet;
		var timesheetStatusTxt = DynCls_Ajx_responseTxt;
		if(timesheetStatusTxt != 0)
			{
				var rspsArr = timesheetStatusTxt.split("|");
				var rspsUserNameArr = rspsArr[4].replace(/\,/g,"|");
				if(rspsArr[4] != '')
					form.chksnoid.value = rspsArr[4];

				var htmDisplay = '<div style="height:25px; background-color:#00B9F2; font-family:Tahoma, Helvetica, sans-serif; font-size:small; font-weight:bold; color:Captiontext; vertical-align:middle; text-align:left;"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr valign="middle"><td id="captionTd" style="width:96%; padding:5px; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; color:#000000;" valign="middle"><b>Confirmation</b></td></tr></table></div><table style="width:100%; height:95%; background-color:#FFFFFF;" border="0"> <tr><td class="alert-time-msg"><b>Below employees have existing timesheets for the date range selected.</b></td></tr><tr><td class="alert-time-msg"><div style="overflow:auto; height:35px;">'+rspsArr[0]+'</div></td></tr><tr><td class="alert-time-msg">Would you like to continue submitting additional timesheets for the same date range?</td></tr><tr><td class="alert-time-msg">Click on <b>YES</b> to continue submitting additional timesheets for the above employees along with the other selected employees.</td></tr><tr><td class="alert-time-msg">Click on <b>NO</b> to exclude the above listed employees and continue submitting for the remaining selected employees. </td></tr><tr><td class="alert-time-msg">Click on <b>CANCEL</b> to return to the screen. </td></tr><tr><td class="alert-time-msg"><b>NOTE:</b> Employee records can be selected using the check boxes manually and removed using the link "Remove". </td></tr><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmYes" id="btnConfirmYes" value="Yes" onClick="javascript: getConfirmTimesheetAlert(\'1\',\''+rspsUserNameArr+'\');"  class="time-alert-button" /> &nbsp; <input type="button" name="btnConfirmNo" id="btnConfirmNo" value="No" onClick="javascript: getConfirmTimesheetAlert(\'2\',\''+rspsUserNameArr+'\');"  class="time-alert-button" /> &nbsp;<input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmTimesheetAlert(\'-1\',\''+rspsUserNameArr+'\');" class="time-alert-button" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></table>';
			callPopupWindow(htmDisplay);
			return;
			}else
			{
				getConfirmTimesheetAlert ('1','');
			}
				
	}
	
	function callPopupWindow(htmDisplay)
	{
				var obj  = document.getElementById("dynsndiv");
				var obj1 = document.getElementById("SaveAlert");
				obj1.innerHTML = htmDisplay;
				var left = (window.document.body.clientWidth / 2) - 255;
				var top  = (window.document.body.clientHeight / 2) - 200;
				
				with(obj)
				{
					if(document.body.scrollHeight > window.document.body.clientHeight)
					{
						style.width  = document.body.scrollWidth;
						style.height = document.body.scrollHeight;
					}
					else
					{
						style.width  = "100%";
						style.height = "100%";
					}				
					
					style.zIndex = "99";
					style.position = "absolute";
					style.filter = "alpha(opacity=30)";
					style.backgroundColor = "#AAAAAA";
					style.opacity = ".3";
					style.display = "block";
				}
				
				with(obj1)
				{
					style.position = "absolute";
					style.top = top+"px";
					style.left = left+"px";
					style.zIndex = 2000;
					style.visibility = "visible";
					style.width = "500px";
					style.display = "block";
				}
				return;
			
	}
	function getConfirmTimesheetAlert (status,resp_val){
		var colcount = document.getElementById('ratecount').value;
		form=document.sheet;
	//	var act = form.aa.value;
		var v_heigth = 300;
		var v_width  = 600;
		var top1=(window.screen.availHeight-v_heigth)/2;
		var left1=(window.screen.availWidth-v_width)/2;
		switch (status){
			case '1':	
			
				val = form.rowcou.value;
				var data="";
				var y=0;
				for(i=1;i<=val;i++)
				{ 
					var billstr = '';
				    var hourstr = '';
				    
				    for(var x=0; x <=colcount-1; x++)
				    {
					    if(hourstr == '')
					    {						    
							if (x == 0) {
								hourstr = document.getElementById('daily_rate_'+x+'_'+i).value;;
							}else{
								hourstr = ',' + document.getElementById('daily_rate_'+x+'_'+i).value;
							}
					    }
					    else
					    {
						    hourstr += ","+document.getElementById('daily_rate_'+x+'_'+i).value;
					    }
					    
					    if(billstr == '')
					    {
						    if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
						    {
							    billstr = 'Y';
						    }
						    else
						    {
							    billstr = 'N';
						    }
						    
					    }
					    else
					    {
						    if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
						    {
							    billstr += ',Y';
						    }
						    else
						    {
							    billstr += ',N';
						    }
					    }
				    }
			    	// going to check for date range if the assignment / Employee as rate values or not
			    	// If Null then making NULL for Rate Values.
			    	form=document.sheet;
					var checking_from = form.servicedatefrom.value;
					var checking_to = form.servicedateto.value;
					var empUsernames = document.getElementById('empusername_'+y).value;
					
					var content = "rtype=getTimesheetStatus&multiple=YES&moduleName=Accounting&dateRangeFilled=NO&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames;
					var url = "/BSOS/Include/getAsgn.php";
					DynCls_Ajax_result(url,'rtype',content,"");
					var timesheetStatusTxt = DynCls_Ajx_responseTxt;
					
					if (timesheetStatusTxt == "0") {
						var elements = hourstr.split(',');
						emptyHrsStr ='';
						
						for (var z = 0; z <= elements.length- 1;z++) {
							emptyHrsStr+=',';
						}							
						hourstr = emptyHrsStr;
					}
					if(data=="")
						data=form.empusername[i].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i]).replace(":",".")+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i]);
					else
						data+="^"+form.empusername[i].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i]).replace(":",".")+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i]);

						
				}
				form.timedata.value=data;
				form.action=window.location.href;
				form.submit();
			break;

			case '2':
				  var existTimesheetID = form.chksnoid.value;
				  var existchkarr = existTimesheetID.split(',');
				  var e = document.getElementsByName('auids[]');
				  var totalchkbox = e.length;
				  var val;
				  var empNameID = "";
				  form.action="multitimesheet.php";
				  for(var i=0; i<totalchkbox; i++)
				  {
					val = e[i].value;
					var chkid = val.split('_');
					   if(!chkArrayVAl(existchkarr,chkid[1]))
					   {
							if(i == 0)
								empNameID = val;
							else 
								empNameID += ","+val;
						}
				  }
				  removeSelectedRows(empNameID);				   
			break;
	
			case '-1':
			break;
			y++;
		}
		document.getElementById("dynsndiv").style.display = "none";
		document.getElementById("SaveAlert").style.display = "none";
	}
	
	function getValidateTimesheet(timesheetType)
	{
		var timesheetStatusTxt = DynCls_Ajx_responseTxt;
		
		dateRangeFilled = "NO";
		if(timesheetType == "single")
		{
			if(timesheetStatusTxt != 0)
			{
				var rspsArr = timesheetStatusTxt.split("|");
			    
				
					if(window.location.href.indexOf('/BSOS/MyProfile/Timesheet/timesheet.php?module=MyProfile') > 0)
						var alMsg ='<tr><td class="alert-time-msg"><b>A Timesheet for the below dates already exists.</b></td></tr><tr><td class="alert-time-msg"><div style="height:auto; overflow:auto">'+rspsArr[0]+'</div></td></tr>';
					else
						var alMsg ='<tr><td class="alert-time-msg"><b>Timesheet exists for this employee with below dates.</b></td></tr><tr><td class="alert-time-msg"><div style="height:auto; overflow:auto">'+rspsArr[0]+'</div></td></tr>';
								
				var htmDisplay = '<div style="height:25px; background-color:#00B9F2; font-family:Tahoma, Helvetica, sans-serif; font-size:small; font-weight:bold; color:Captiontext; vertical-align:middle; text-align:left;"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr valign="middle"><td id="captionTd" style="width:96%; padding:5px; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; color:#000000;" valign="middle"><b>Confirmation</b></td></tr></table></div><table style="width:100%; height:95%; background-color:#FFFFFF;" border="0">'+alMsg+'<tr><td class="alert-time-msg">Click on <b>Yes</b> to continue creating another timesheet for the same date range or <b>Cancel</b> to return to screen.</td></tr><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmYes" id="btnConfirmYes" value="Yes" onClick="javascript: getConfirmAlert(\'1\');"  class="time-alert-button" /> &nbsp; <input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert(\'-1\');" class="time-alert-button" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></table>';
				document.getElementById("SaveAlert").innerHTML = htmDisplay;
				
				return;
			}
			else
			{
				singleTimesheetSubmit();
			}
		}
		else
		{
			//multiplesheet case..
			if(timesheetStatusTxt != 0)
			{
				var rspsArr = timesheetStatusTxt.split("|");
				if(rspsArr[4] != '')
					document.sheet.chksnoid.value = rspsArr[4];
				var htmDisplay = '<div style="height:25px; background-color:#00B9F2; font-family:Tahoma, Helvetica, sans-serif; font-size:small; font-weight:bold; color:Captiontext; vertical-align:middle; text-align:left;"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr valign="middle"><td id="captionTd" style="width:96%; padding:5px; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; color:#000000;" valign="middle"><b>Confirmation</b></td></tr></table></div><table style="width:100%; height:95%; background-color:#FFFFFF;" border="0"> <tr><td class="alert-time-msg"><b>Timesheets exists for the below employees for this date range ('+rspsArr[1]+').</b></td></tr><tr><td class="alert-time-msg"><div style="overflow:auto">'+rspsArr[0]+'</div></td></tr><tr><td class="alert-time-msg">Click on <b>Yes</b> to continue creating another timesheet for the same date range or <b>Cancel</b> to return to screen.</td></tr><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmYes" id="btnConfirmYes" value="Yes" onClick="javascript: getConfirmAlert(\'2\');"  class="time-alert-button" /> &nbsp; <input type="button" name="btnConfirmNo" id="btnConfirmNo" value="No" onClick="javascript: getConfirmAlert(\'3\');"  class="time-alert-button" /> &nbsp;<input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert(\'-1\');" class="time-alert-button" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></table>';
				document.getElementById("SaveAlert").innerHTML = htmDisplay;
				return;
			}
			else
			{
				form=document.sheet;
				form.submit();
			}
		}
	}
	function singleTimesheetSubmit()
	{
		form=document.sheet;
			
		if(form.module.value == 'MyProfile')
			form.action = '../../../BSOS/MyProfile/Timesheet/savetime.php';
		else if(form.module.value == 'Client')
			form.action = '/BSOS/Client/savetime.php';
		
		form.submit();
	}
	function SearchEmployeeWin()
	{
		var colcount = document.getElementById('ratecount').value;
		var v_height = 560;
		var v_width  = 900;
		var form=document.forms[0];
		
		val = form.rowcou.value;		
		var data="";
		for(i=1;i<=val;i++)
		{
			var billstr = '';
			var hourstr = '';
			
			for(var x=0; x <=colcount-1; x++)
			{
				if(hourstr == '')
				{					
					if (x == 0) {
						hourstr = document.getElementById('daily_rate_'+x+'_'+i).value;;
					}else{
						hourstr = ',' + document.getElementById('daily_rate_'+x+'_'+i).value;
					}

				}
				else
				{
					hourstr += ","+document.getElementById('daily_rate_'+x+'_'+i).value;
				}
				
				if(billstr == '')
				{
					if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
					{
						billstr = 'Y';
					}
					else
					{
						billstr = 'N';
					}
					
				}
				else
				{
					if(document.getElementById('daily_rate_billable_'+x+'_'+i).checked)
					{
						billstr += ',Y';
					}
					else
					{
						billstr += ',N';
					}
				}
			}
			if(data=="")
				data=form.empusername[i].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i]).replace(":",".")+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i]);
			else
				data+="^"+form.empusername[i].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+billstr+"|"+hourstr+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i]).replace(":",".")+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i]);	
		}
		form.timedata.value=data;
		var top1=(window.screen.availHeight-v_height)/2;
		var left1=(window.screen.availWidth-v_width)/2;
		var servicedatefrom=document.forms[0].servicedatefrom.value;
		var servicedateto=document.forms[0].servicedateto.value;
		var url="SelectEmployeeLink.php?servicedatefrom="+servicedatefrom+"&servicedateto="+servicedateto;
		remote=window.open(url,'SearchEmployees',"width="+v_width+"px,height="+v_height+"px,resizable=yes,scrollbars=yes,status=0,top="+top1+"px,left="+left1+"px");// siva prasanth
		remote.focus();
	}	
	function doCancelMultiEdit()
	{
		var form = document.forms[0];
		var statval = form.statval.value;
		if(statval == 'statapproved')
			form.action = "/BSOS/Accounting/Time_Mngmt/showdetails.php";
		else if(statval == 'statsubmitted')
			form.action = "/BSOS/Accounting/Time_Mngmt/showfxdet.php";
		var module = form.module.value;
		if(module == 'MyProfile')
			form.action = "/BSOS/MyProfile/Timesheet/show.php";
		else if(module == 'Client')
			form.action = "/BSOS/Client/timesheets.php";
		if(form.rowId.value != '')
			form.action = "showdetails.php";
		form.submit();
	}
	function focus_clear(val)
	{
		var chkval= val;
		val++;
		var taskchk=document.forms[0].task[val].value;		
		if(taskchk == "Enter timesheet notes here")
		{
			document.forms[0].task[val].value = "";
			var id="multipletask"+chkval;
			document.getElementById(id).className="";
		}	
	}	
	function blur_task(val)
	{
		var chkval= val;
		val++;
		var taskchk=document.forms[0].task[val].value;
		if(taskchk == "")
		{
			document.forms[0].task[val].value = "Enter timesheet notes here";
			var id="multipletask"+chkval;
			document.getElementById(id).className="txtelement";
		}	
	}
	function change_font(val)
	{
	//	var chkval= val;
	//	val++;
	//	var taskchk=document.forms[0].task[val].value;		
	//	if(document.forms[0].task[val].value == "Enter timesheet notes here" || document.forms[0].task[val].value == '')
	//	{
	//		var id="multipletask"+chkval;
	//		document.getElementById(id).className="txtelement";
	//	}
	//	else
	//	{
	//		var id="multipletask"+chkval;
	//		document.getElementById(id).className="";
	//	}
	}
	
	var selectObjGlbl;
	var selIndxGlbl;
/* modify the function for alert message when no aasignment is present for selecting date -vipin 19/12/08 */	
	function chkAsgn(Asgn,spanId,empUser,selObj)
	{	
		var form = document.sheet;
		var len = spanId.length;		
		var id = spanId;
		var chkDate = form.sdates[id].value;
		var module =  form.module.value;
		var hdnDate = form.hdnDate.value;
		var chkdate_arr;
		if( window.location.href.indexOf('/BSOS/Include/edittimemulti.php')>0)
		{
			if(chkDate.length >10)
			{
				chkdate_arr = chkDate.split(" - ");
				chkdate_arr_arr = chkdate_arr[0].split('/');
				chkDate = chkdate_arr_arr[2]+"-"+chkdate_arr_arr[0]+"-"+chkdate_arr_arr[1];				
			}
		}
		if(typeof selObj !='undefined')
		selectObjGlbl=selObj;		
		id = id-1;
		var content="assignEndDate="+chkDate+"&Asgn="+Asgn+"&new_user="+empUser+"&r="+id+"&module="+module+"&hdnDate="+hdnDate;
		Ajax_result('/BSOS/Include/getAsgn.php?','',content,'');		
	}
/* modify the function for alert message when no aasignment is present for selecting date -vipin 19/12/08 */	
	function Ajx_responseDisplay()
	{
		var form = document.sheet;		
		var getValue = Ajx_responseTxt.split("^|^");
		var id = getValue[1];
		var hdnDate = getValue[2];
		var optoin_Array = getValue[0].split("^");
		if(optoin_Array!="|0|||")
		{			
			msid='clientId'+id;
			var aRow = document.getElementById(msid);
			var selValue = aRow.options[aRow.selectedIndex].value;
			aRow.options.length= null;
			var selected = false;
			var module = form.module.value;
			var selfCompany = '';
			if(module == 'Client')
			{
				selfCompany = form.selfCompany.value;			
			}
			
			for(i = 0; i< optoin_Array.length; i++)
			{
				optionvalues = optoin_Array[i].split("|");			
				
				aRow.options[i] = new Option(optionvalues[3], optionvalues[1]);
				aRow.options[i].id = optionvalues[0];
				aRow.options[i].title = optionvalues[2];	
				if(optionvalues[1] == selValue)
				{
					aRow.options[i].selected = true;			
					var selected = true;
				}			
			}
			selectObjGlbl.selindex=selectObjGlbl.options[selectObjGlbl.selectedIndex].value;
			if(!selected)
			{
				getMultipleRate(id-1);
			}
				//getBillT(id-1);
			//document.getElementById(Ajx_DisBoxName).innerHTML=Ajx_responseTxt;
			return false;
		}
		else
		{
			alert("There are no assignment available to create Timesheet");
			selectObjGlbl.options[hdnDate].selected=true;
			/*for(cnt=0;cnt<selectObjGlbl.options.length;cnt++){
					if(selectObjGlbl.options[cnt].value == datePrev){
						selectObjGlbl.options[cnt].selected=true;
					}
				}*/
			/*if(selectObjGlbl.selindex==''){
				selectObjGlbl.selectedIndex=0;
			}
			else{
				for(cnt=0;cnt<selectObjGlbl.options.length;cnt++){
					if(selectObjGlbl.options[cnt].value==selectObjGlbl.selindex){
						selectObjGlbl.options[cnt].selected=true;
					}
				}	
			}*/
			return false;
		}		
	}
	function delEmployee()
	{
		form=document.sheet;
		val=form.rowcou.value;
		var selValue = valSelected();
		if(selValue == "")
		{
			alert("You have to select atleast one Timesheet entry to delete from the Available List.");
			return;
		}
		if( window.location.href.indexOf('/BSOS/Include/edittimemulti.php')>0 )
		{
			form.action = "/BSOS/Include/edittimemulti.php";
			if(form.timefile.value != "")
			{
				var fileConfirm = (confirm("If you delete row(s), you need to upload the file again. Do you want to continue?"));	
				if(fileConfirm)
					form.timefile.value == "";
				else
					return;
			}
			form.newrowcou.value=parseInt(val)+1;
		}
		else
			form.action = "multitimesheet.php";
			
		
		var selValArray = selValue.split(",");
		if(val == selValArray.length)
		{
			alert("Your Timesheets must have atleast one entry. You can't delete all the entries.");
			return;
		}
		var data="";
		var count = 0;
		
		for(i=1;i<=val;i++)
		{		
			if(chkArrayVAl(selValArray,i))
			{
				if(data=="")
					data=form.sdates[i].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i]).replace(":",".")+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
				else
					data+="^"+form.sdates[i].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i]).replace(":",".")+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i])+"|"+getVal(form.qbid[i]);
			}
		}
		form.timedata.value=data;		
		form.submit();	
	}
	function chkArrayVAl(arrName,chkVal) // value checks in array and returns true or false 
	{
		var flag = true;
		for (arrcou = 0; arrcou< arrName.length;  arrcou++)
		{
			if(arrName[arrcou] == chkVal)
			{
				flag = false;
				break;
			}
		}		
		return flag;
	}
	//to delete attached file in editmulti timesheet page...
	function doMultiDeleteFile()
	{
		if(confirm("Do you want to delete attached file for this Timesheets?"))
		{
			var form=form=document.sheet;
			var val=Number(form.rowcou.value);
			
			getMultiTimeDataFile(val);
			form.action="edittimemulti.php";
			form.acctype.value="filedelete";
			form.submit();
		}
	}
	function getMultiTimeDataFile(val)
	{
		form=document.sheet;
		var data="";
		for(i=1;i<=val;i++)
		{
			try
			{
				if(window.location.href.indexOf('/BSOS/Include/edittimemulti.php')>0)
					var firstVal = form.sdates[i].value;
				else
					var firstVal = form.empusername[i].value;
				if(data=="")
					data=firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i]).replace(":",".")+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i]);
				else
					data+="^"+firstVal+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.hourstype[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.hours[i]).replace(":",".")+"|"+form.client[i].options[form.client[i].selectedIndex].id+"|"+getVal(form.jobtype[i])+"|"+getVal(form.class_type[i]).replace(":",".")+"|"+getVal(form.sdates[i])+"|"+getVal(form.status[i])+"|"+getVal(form.auser[i])+"|"+getVal(form.sno_ts[i])+"|"+getVal(form.edates[i]);
			}
			catch(e)
			{
			  return;
			}
	
		}
		form.timedata.value=data;
	}
	
//To convert number to decimal with 2 digits.-- vani
function NumberFormatted(amount)
{
	var i = parseFloat(amount);
	if(isNaN(i)) { i = 0.00; }
	var minus = '';
	if(i < 0) { minus = '-'; }
	i = Math.abs(i);
	i = parseInt((i + .005) * 100);
	i = i / 100;
	s = new String(i);
	if(s.indexOf('.') < 0) { s += '.00'; }
	if(s.indexOf('.') == (s.length - 2)) { s += '0'; }
	s = minus + s;
	return s;
}
function setHiddenDateDropList(obj)
{
	document.getElementById('hdnDate').value = obj.selectedIndex;
}