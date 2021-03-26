var elehref;

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

/*function goMultiTs()
{
	PopMsgHeadArr['multi_timesheet']="Create Multiple Timesheets";
	PopMsgFLineArr['multi_timesheet']="Timesheets created using the \"Create Multiple Timesheets\" link cannot be used to generate gross pay. Use the New Timesheet link for the hours to be used in generating gross pay.";
	PopMsgQueArr['multi_timesheet']="Click <b>OK</b> to continue creating multiple timesheets.<br>Click <b>Cancel</b> to go back to the screen.";
	PopMsgSLineArr['multi_timesheet']="";
	PopMsgExtMsgArr['multi_timesheet']="";
	display_Dynmic_Message('multi_timesheet','cancel','ok','','displayTimeGridAlert');
}*/
function displayTimeGridAlert(retstatus)
{
     /*switch(retstatus)
     {
    	case 'cancel':	break;
    	case 'ok':*/
            //window.location.href="multitimesheet.php?module=Accounting";
	    var v_width  = 1200;
	    var v_heigth = 600;
	    var top=(window.screen.availHeight-v_heigth)/2;
	    var left=(window.screen.availWidth-v_width)/2;
	    remote=window.open('multitimesheet.php?module=Accounting','Multiple_Timesheets','width=1200,height=600,resizable=yes,scrollbars=yes,status=0,left='+left+',top='+top);
	    remote.focus();
           // break;		
     //}
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
		remote=window.open('../calendar.php?mn='+mn+'&dy='+dy+'&yr='+yr+'&val='+val,'cal','width=200,height=200,resizable=no,scrollbars=no,status=0,left='+left+',top='+top);
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
    
    if(trim(tval).length > 0)
    {
        if(!isNaN(tval))
        {
            if(tval.indexOf(".")<0 )
            {
                if(Number(target.value)>=0 && Number(target.value)<10)
                    target.value=target.value+":00";
                else if(Number(target.value)>=10 && Number(target.value)<=24)
                    target.value=target.value+":00";
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
                else if(Number(target.value)>=10 && Number(target.value)<=24)
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
    doshow(target);
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
        tval=form.thours[i].value;
        tval=tval.replace(":",".")

        if(isNaN(tval))
        {
            total=total;
        }
        else
        {
            temp=Number(tval);
            dtotal=temp.toFixed(2);
            drr=dtotal.split(".");
            dhours=parseInt(drr[0],10)+dhours;
            dmin=parseInt(drr[1],10)+dmin;
        }
    }

    if(dmin>=60)
    {
        dres=parseInt(dmin/60,10);
        drem=dmin%60;
        dtotal=dhours+dres;
        if(drem<10)
            drem="0"+drem;
        totalhr.innerText=dtotal+":"+drem;
    }
    else
    {
        if(dmin<10)
            dmin="0"+dmin;

        totalhr.innerText=dhours+":"+dmin;
    }
}

function doShowT1(target)
{
    doshow(target);
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
        tval1=form.othours[i].value;
        tval1=tval1.replace(":",".")

        if(isNaN(tval1))
        {
            total1=total1;
        }
        else
        {
            temp1=Number(tval1);
            dtotal1=temp1.toFixed(2);
            drr1=dtotal1.split(".");
            dhours1=parseInt(drr1[0],10)+dhours1;
            dmin1=parseInt(drr1[1],10)+dmin1;
        }
    }

    if(dmin1>=60)
    {
        dres1=parseInt(dmin1/60,10);
        drem1=dmin1%60;
        dtotal1=dhours1+dres1;
        if(drem1<10)
            drem1="0"+drem1;
        othour.innerText=dtotal1+":"+drem1;
    }
    else
    {
        if(dmin1<10)
            dmin1="0"+dmin1;

        othour.innerText=dhours1+":"+dmin1;
    }
}

function doShowT2(target)
{
    doshow(target);
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
        tval1=form.dbhours[i].value;
        tval1=tval1.replace(":",".")

        if(isNaN(tval1))
        {
            total1=total1;
        }
        else
        {
            temp1=Number(tval1);
            dtotal1=temp1.toFixed(2);
            drr1=dtotal1.split(".");
            dhours1=parseInt(drr1[0],10)+dhours1;
            dmin1=parseInt(drr1[1],10)+dmin1;
        }
    }

    if(dmin1>=60)
    {
        dres1=parseInt(dmin1/60,10);
        drem1=dmin1%60;
        dtotal1=dhours1+dres1;
        if(drem1<10)
            drem1="0"+drem1;
        dbhour.innerText=dtotal1+":"+drem1;
    }
    else
    {
        if(dmin1<10)
            dmin1="0"+dmin1;

        dbhour.innerText=dhours1+":"+dmin1;
    }
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
		if(client == '')
			var client=timesheet.client.value;
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
// added to close the exported pop up window
function doCancel5_exported()
{
	window.close();
    
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

function isHours(field,name)
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
	if((str>24 || str<0) && (str!=""))
	{
        alert("You have entered Invalid Hours, Please enter a value between 0 and 24.");
        field.select();
        field.focus();
        return false;
    }
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
	if((str>24 || str<0) && (str!=""))
	{
        alert("You have entered Invalid Hours, Please enter a value between 0 and 24.");
        field.select();
        field.focus();
        return false;
    }
	return true;
}
function getEmp()
{
	form=document.sheet;
	form.action="timesheet.php";
	form.rowcou.value = "";
	form.val.value = "";
	form.submit();
}
function validate(act)
{
	
	form=document.sheet;
	val=form.rowcou.value;
	var flag=true;
	var flag1=true;
	var arrTHPD = new Array();
	var arrIndex = new Array();
	var k = 0;
    
    var data="";
	var ttotal=0;
	var ttotal1=0;
	var ttotal2=0;
  	for(i=1;i<=val;i++)
	{
        if(CheckSpecChars(form.task[i]))
        {
            alert("The Task Details field do not accept  ^ |  characters.Please re-enter Task details.");
            form.task[i].focus();
            form.task[i].select();
            return;
        }
        ttotal+=form.thours[i].value;
		 ttotal1+=form.othours[i].value;
		 ttotal2+=form.dbhours[i].value;
            if(isHours(form.thours[i],"Hours") && isOverHours(form.othours[i],"OverTime Hours") && isHours(form.dbhours[i],"Double Hours"))
            {
					var tothrs=addHours(form.thours[i].value.replace(":","."),form.othours[i].value.replace(":","."));
					tothrs=addHours(tothrs,form.dbhours[i].value.replace(":","."));
					
					if(tothrs > 24)
					{
						alert("Total Working Hours is more than 24 Hrs. Please Re-enter.");
						form.othours[i].focus();
						form.othours[i].select();
						return;
					}
				
					if(data=="")
						data=form.sdates[i].options[form.sdates[i].selectedIndex].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.thours[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.othours[i])+"|||"+getVal(form.dbhours[i]);
					else
						data+="^"+form.sdates[i].options[form.sdates[i].selectedIndex].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.thours[i])+"|"+getBilVal(form.billable[i])+"|"+getVal(form.othours[i])+"|||"+getVal(form.dbhours[i]);
					flag=true;
					flag1=true;
					arrDay = form.sdates[i].options[form.sdates[i].selectedIndex].value.split("-");
					day = arrDay[2];
	
					if(typeof(arrTHPD[day]) == "undefined")
					{
						arrTHPD[day] = getVal(form.thours[i]).replace(":",".");
						arrIndex[k++] = day;
					}
					else
						arrTHPD[day] = addHours(arrTHPD[day] , getVal(form.thours[i]).replace(":","."));
				
            }
            else
            {
              flag=false;
              break;
            }
       }
		
	if(flag)
	{
		for(i=0; i<k; i++)
		{
			if(arrTHPD[arrIndex[i]] > 24)
			{
				for(j=1;j<=val;j++)
				{
					arrDay = form.sdates[j].options[form.sdates[j].selectedIndex].value.split("-");
					day = arrDay[2];
					if(day == arrIndex[i])
					{
						strDate = form.sdates[j].options[form.sdates[j].selectedIndex].text;
						form.thours[j].select();
						form.thours[j].focus();
						break;
					}
				}
				alert("Total working hours are more than 24 Hrs on '" + strDate + "'.\n Please Re-enter.");
				return;
			}
		}
		if(form.module.value == 'MyProfile')
			form.action = '../../../BSOS/MyProfile/Timesheet/savetime.php';
		else if(form.module.value == 'Client')
			form.action = '/BSOS/Client/savetime.php';			
		
		form.aa.value=act;
		form.timedata.value=data;			
		form.submit();
	}
}

function doDelete(val)
{
	numAddrs = numSelected_TimeSheet();
	valAddrs = valSelected_TimeSheet();
	if (numAddrs < 0)
	{
		alert("There are no entries to delete.");
		return false;
	}
	else if (!numAddrs)
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
function getTimeData(val)
{
	form=document.sheet;
	var data="";
	for(i=1;i<=val;i++)
	{
        try
        {
		 if(data=="")
			data=form.sdates[i].options[form.sdates[i].selectedIndex].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.thours[i]).replace(":",".")+"|"+getBilVal(form.billable[i])+"|"+getVal(form.othours[i]).replace(":",".")+"|||"+getVal(form.dbhours[i]).replace(":",".");
		else
			data+="^"+form.sdates[i].options[form.sdates[i].selectedIndex].value+"|"+form.client[i].options[form.client[i].selectedIndex].value+"|"+getVal(form.task[i])+"|"+getVal(form.thours[i]).replace(":",".")+"|"+getBilVal(form.billable[i])+"|"+getVal(form.othours[i]).replace(":",".")+"|||"+getVal(form.dbhours[i]).replace(":",".");	
			
    	}
    	catch(e)
    	{
        return;
        }
	}
	form.timedata.value=data;
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
				data=form.sdates[j].options[form.sdates[j].selectedIndex].value+"|"+form.client[j].options[form.client[j].selectedIndex].value+"|"+getVal(form.task[j])+"|"+getVal(form.thours[j]).replace(":",".")+"|"+getBilVal(form.billable[j])+"|"+getVal(form.othours[j]).replace(":",".")+"|||"+getVal(form.dbhours[j]).replace(":",".");
			else
				data+="^"+form.sdates[j].options[form.sdates[j].selectedIndex].value+"|"+form.client[j].options[form.client[j].selectedIndex].value+"|"+getVal(form.task[j])+"|"+getVal(form.thours[j]).replace(":",".")+"|"+getBilVal(form.billable[j])+"|"+getVal(form.othours[j]).replace(":",".")+"|||"+getVal(form.dbhours[j]).replace(":",".");	
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


function chke()
{
	var e=	form=document.forms[0].chk;
	if(e.checked==true)
		checkAll_TimeSheet();
	else
		clearAll_TimeSheet();
}

function getBillT(j)
{
	form=document.sheet;

	var earn=0;
	j=parseInt(j)+1;

	form.billable[j].disabled = false;
	var ass=form.client[j].options[form.client[j].selectedIndex].value;
	var clientid = form.client[j].options[form.client[j].selectedIndex].id;
	var jobtype = form.client[j].options[form.client[j].selectedIndex].title;
	
	if(ass!="AS" && ass!="OB")
		earn=ass.search("earn");
		
	if(((ass!="AS" && ass!="OB" && ass!="OV" && earn<0) || ((ass=="AS" && (clientid != '' && clientid != 0))|| (ass=="OB" && (clientid != '' && clientid != 0)) ||  (ass=="OV" && (clientid != '' && clientid != 0)) || (earn>0 && (clientid != '' && clientid != 0)))))
	{
		if(jobtype != 'Internal Direct' && jobtype != 'Internal Temp/Contract')
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
function doSend(mod)
{
    if(mod == 'ess') 
    {
        window.location.href = "doreminder.php";
    }
    else 
    {
       window.location.href = "doreminderCSS.php";
    }
}

function doSendReminder(val)
{
    if(val > 0)
    {
        var form=document.myname;
        //form.tnotes.value = trimAll(form.tnotes.value);
        form.matter2.value = trimAll(form.matter2.value);
        if(isNotEmpty(form.txtsdate,"Start Date") && isNotEmpty(form.txtedate,"End Date") && ValidateSendReminders(form.txtsdate,form.txtedate))
        {
            disablealllinks();
            form.submit();
            document.getElementById("mainpage").style.display       = "none";
            document.getElementById("processpage").style.display    = "block";
        }
    }
    else
    {
        alert('In order to use timesheets reminder, a default external email accounts needs to be setup in Akken. Please setup external email account for Super User.');
        return;
    }
}

/*function SendReminders(sts)
{
    var form = document.timesheet;
    var mobj = document.getElementById("dynsndiv");
    var sobj = document.getElementById("divpopup");
    
    if(sts == "-1")
    {
        mobj.style.display = "none";
        sobj.style.display = "none";
    }
    if(sts == "1")
    {
        if(isNotEmpty(form.txtsdate,"Start Date") && isNotEmpty(form.txtedate,"End Date") && ValidateSendReminders(form.txtsdate,form.txtedate))
        {
            mobj.style.display = "none";
            sobj.style.display = "none";
            
            form.action = "doreminder.php";
            form.submit();
        }
    }
}*/

function ValidateSendReminders(sobj,eobj)
{
	var re_date = /^\s*(\d{1,2})\/(\d{1,2})\/(\d{2,4})\s*$/;
	
	if(!re_date.exec(sobj.value))
	{
		alert("Invalid Date/Date Format.\n\nPlease enter date in (mm/dd/yyyy) format.");
		sobj.select();
		return false;
	}
	else if(!re_date.exec(eobj.value))
	{
		alert("Invalid Date/Date Format.\n\nPlease enter date in (mm/dd/yyyy) format.");
		eobj.select();
		return false;
	}
	
	re_date.exec(sobj.value);
	var smon = Number(RegExp.$1), sday = Number(RegExp.$2), syr = Number(RegExp.$3);
	
	re_date.exec(eobj.value);
	var emon = Number(RegExp.$1), eday = Number(RegExp.$2), eyr = Number(RegExp.$3);
	
	if((syr > eyr) || (syr == eyr && smon > emon) || (syr == eyr && smon == emon && sday > eday))
	{
		alert("End Date should be greater than or equal to Start Date");
		eobj.select();
		return false;
	}
	return true;
}

function disablealllinks()
{
  var link=document.getElementsByTagName("a");
  var linkcount=link.length;
  for(var i =0; i < linkcount; i++)
   {
	 var objmenu=document.getElementsByTagName("a")[i].href;
	 document.getElementsByTagName("a")[i].disabled = true;
	 document.getElementsByTagName("a")[i].style.cursor='default';
	 document.getElementsByTagName("a")[i].href="#";
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
function doPrintInvoice(id,invoice,rowAsgn,invoicepage)
{
	var v_heigth = window.screen.availHeight-50;
	var v_width  = window.screen.availWidth-10;
	
	
	//var parchkedrows = "<?php echo $tsno."|".$cun."|".$clival; ?>";
	path = "/BSOS/Accounting/Time_Mngmt/printtimesheet.php?addr1="+id+"&invoice="+invoice+"&rowAsgn="+rowAsgn+"&frominvoicepage=yes";
	remote=window.open(path,"timesheets","width="+v_width+"px,height="+v_heigth+"px,statusbar=yes,menubar=no,scrollbars=yes,left=0,top=0,dependent=yes,resizable=yes");
	remote.focus();
}


function doPrint(id,invoice,rowAsgn)
{
	var v_heigth = window.screen.availHeight-50;
	var v_width  = window.screen.availWidth-10;
	
	
	//var parchkedrows = "<?php echo $tsno."|".$cun."|".$clival; ?>";
	path = "/BSOS/Accounting/Time_Mngmt/printtimesheet.php?addr1="+id+"&invoice="+invoice+"&rowAsgn="+rowAsgn;
	remote=window.open(path,"timesheets","width="+v_width+"px,height="+v_heigth+"px,statusbar=yes,menubar=no,scrollbars=yes,left=0,top=0,dependent=yes,resizable=yes");
	remote.focus();
}

function doPrintInvoiceExportedTM(id,invoice,rowAsgn)
{
	var v_heigth = window.screen.availHeight-50;
	var v_width  = window.screen.availWidth-10;
	
	var mode = "&mode=approvedexp";
	
	var path = "/include/tcpdf/print/print_timesheets.php?from=popup&addr1="+id+"&invoice="+invoice+"&rowAsgn="+rowAsgn+mode; 
	remote=window.open(path,"timesheets","width="+v_width+"px,height="+v_heigth+"px,statusbar=yes,menubar=no,scrollbars=yes,left=0,top=0,dependent=yes,resizable=yes");
	remote.focus();
}

function doPrintExported(id,invoice,rowAsgn)
{
	var v_heigth = window.screen.availHeight-50;
	var v_width  = window.screen.availWidth-10;
		
	var mode = "&mode=Exported";
	var path = "/include/tcpdf/print/print_timesheets.php?from=popup&addr1="+id+"&invoice="+invoice+"&rowAsgn="+rowAsgn+mode; 
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
////// Related to showfxdet.php in Accounitng/Time_mngmt
function doApprove()
{
	
	form=document.forms[0];
	sdates=form.sdatets.value.split("|");
    tscount=form.stscount.value.split("|");
    flag=0;
    var datenos="";
	var chkboxCount = form.chkcount.value;
	form.chkedrows.value = getCheckedStringAppr(chkboxCount);
	if (form.chkedrows.value == '') 
	{
		alert("Please select atleast one to be approved.");
		return;
	}
	form.action="approvefax.php";
   	form.submit();
}

function doReject()
{
    form=document.forms[0];
    var chkboxCount = form.chkcount.value;
	form.chkedrows.value = getCheckedStringAppr(chkboxCount);
	if (form.chkedrows.value == '') 
	{
		alert("Please select atleast one to be reject.");
		return;
	}
	if (form.details.value == '')
	{
		alert("You are required to enter the notes while rejecting the line items. Please enter the Notes.");
		form.details.focus();
		return;
	}
	form.action="rejectfax.php";
    form.submit();
}

function doFCancel()
{
    var form=document.forms[0];
    var val=form.addr.value;
    var t1=form.t1.value;
    var t2=form.t2.value;
	window.location.href="showfax.php?sno="+val;
}

function getCheckedString(chkboxCount)
{
	var chkString = "";
    for(chk=1;chk<=chkboxCount;chk++)
	{
		if((document.getElementById("chk"+chk)) && (document.getElementById("chk"+chk).type == 'checkbox'))
		{
			if(document.getElementById("chk"+chk).checked == true)
			{
				 var chkval = document.getElementById("chk"+chk).value;
				 if(chkString=="")
					chkString += chkval;
				 else
					chkString += ","+chkval;
			}
		}
	}
	return chkString;
}

function getCheckedStringAppr(chkboxCount)
{
	var chkString = "";
    for(chk=0;chk<=chkboxCount;chk++)
	{
		if((document.getElementById("chk"+chk)) && (document.getElementById("chk"+chk).type == 'checkbox'))
		{
			if(document.getElementById("chk"+chk).checked == true)
			{
				 var chkval = document.getElementById("chk"+chk).value;
				 if(chkString=="")
					chkString += chkval;
				 else
					chkString += ","+chkval;
			}
		}
		if((document.getElementById("chk"+chk)) && (document.getElementById("chk"+chk).type == 'hidden'))
		{
			if(document.getElementById("chk"+chk).type == 'hidden')
			{
				 var chkval = document.getElementById("chk"+chk).value;
				 if(chkString=="")
					chkString += chkval;
				 else
					chkString += ","+chkval;
			}
		}
		
	}
	return chkString;
}

function openwin(val, sno, frm)
{
	var v_heigth = 600;
	var v_width  = 1200;
	var left1=(window.screen.availWidth-v_width)/2;
	var top1=(window.screen.availHeight-v_heigth)/2;
	var module = document.getElementById('module');
	var module_name='';
	if(module !=null){
		module_name= document.getElementById('module').value;
	}
	//-- siva prasanth
    if(frm == 'Clockinout'){
        var url = "/BSOS/Accounting/Time_Mngmt/cico_timesheet_his_details.php?date="+val+"&sno="+sno+"&module="+module_name+"&ts_type="+frm;
       
    }else{
         var url = "/BSOS/Accounting/Time_Mngmt/timesheet1.php?date="+val+"&sno="+sno+"&module="+module_name;
    }
    remote=window.open(url,"","width="+v_width+"px,height="+v_heigth+"px,statusbar=no,menubar=no,scrollbars=yes,left="+left1+"px,top="+top1+"px,dependent=yes,resizable=no");
	remote.focus();
}
function openwin_uom(val, sno)
{
	var v_heigth = 600;
	var v_width  = 1200;
	var left1=(window.screen.availWidth-v_width)/2;
	var top1=(window.screen.availHeight-v_heigth)/2;
	var module = document.getElementById('module');
	var module_name='';
	if(module !=null){
		 module_name= document.getElementById('module').value;
	}
    remote=window.open("/BSOS/Accounting/Time_Mngmt/timesheet1.php?date="+val+"&sno="+sno+"&ts_type=UOM&module="+module_name,"","width="+v_width+"px,height="+v_heigth+"px,statusbar=no,menubar=no,scrollbars=yes,left="+left1+"px,top="+top1+"px,dependent=yes,resizable=no");
	remote.focus();
}
////End

//Function to delete selected Timesheets - vijaya.T
function doDeleteTimeSheet()
{	
	numAddrs = numSelected_TimeSheet();
	valAddrs = valSelected_TimeSheet();
	
	if(document.getElementById('history').value=="no")
		var pageval = 1;
	else
		var pageval =2;
		
	if (numAddrs < 0)
	{
		alert("There are no Timesheets available.");
		return;
	}
	else if (!numAddrs)
	{
		alert("You have to select atleast one timesheet to delete from the Available List.");
		return;
	}
	else
	{
		var content = "recordAction=timeDelete&paraddr="+valAddrs+"&frompg="+pageval;
		var url = "/BSOS/Include/getMadisonStatus.php";
		DynCls_Ajax_result(url,'rtype',content,"getResponseForDelete()");
	}
}

function getResponseForDelete()
{
	var madisonDetailsArr = DynCls_Ajx_responseTxt.split("^");
	var servicedate = document.getElementById('servicedate').value;
	var servicedateto = document.getElementById('servicedateto').value;

	if(madisonDetailsArr[1] == "timeDelete")
	{
		if(madisonDetailsArr[0] == "Y")
		{
			alert("Some of timesheet(s) have already been processed to send to Madison.\nTo Delete, please reset it from Accounting-Timesheets-Processed Timesheets.");
			return;
		}
		else
		{
			if(confirm("Are you sure you want to delete the selected timesheet(s)?"))
			{
				var paraddr = madisonDetailsArr[2];
				var frompg = madisonDetailsArr[4];
				window.location.href = "/include/deleteTimeSheet.php?paraddr="+paraddr+"&frompg="+frompg+"&servicedate="+servicedate+"&servicedateto="+servicedateto;
			}
			else
			{
				document.forms[0].chk.checked=false;
				clearAll_TimeSheet();
			}
		}
	}
}

//Function for approving timesheets.
function doApproveTimeSheets()
{
    form=document.forms[0];
    var numAddrs = numSelected_TimeSheet();
    var valAddrs = valSelected_TimeSheet();
    document.getElementById('Par_Timesheet_Val').value = valAddrs;
    document.getElementById('Approve_Status').value = "frmDoubleClk";
    if (numAddrs < 0)
    {
    	alert("There are no Timesheets to approve.");
    	return;
    }
    else if (!numAddrs)
    {
    	alert("You have to select atleast one Timesheet to approve from the Available Timesheets.");
    	return;
    }
    form.action="approvefax.php";
    form.submit();
}

//Function to reset the PayData List Employees

function doResetPayDataList()
{
	var chkVals = Array();
	var chkLength = 0;
	var e = document.getElementsByTagName("*");
	for (var i=0; i < e.length; i++)
	{
		if (e[i].name == "auids[]" && e[i].checked == true)
		{
			chkVals.push(e[i].value);
			chkLength++;
		}
	}
	if(chkLength == 0)
	{
		alert("Please select atleast one record to reset");
		return;
	}
	else
	{
		if(confirm("Are you sure want to reset the selected record(s)?"))
		{
			var url = "resetpaydatalist.php";
			var rtype='Accounting_PRPayDataList';
			var content = "emplist="+chkVals;
			DynCls_Ajax_result(url,rtype,content,'reloadMyGrid()');
		}
	}
}
// reset the exported data list -- jyothi
function doResetExportedDataList()

{

	var chkVals = Array();

	var chkLength = 0;

	var e = document.getElementsByTagName("*");

	for (var i=0; i < e.length; i++)

	{

		if (e[i].name == "auids[]" && e[i].checked == true)

		{
			chkVals.push("\'"+e[i].value+"\'");
			chkLength++;
		}

	}
	//alert(chkVals);
	if(chkLength == 0)

	{

		alert("Please select atleast one record to reset");

		return;

	}

	else

	{

		if(confirm("Records that have been reset will display again on the timesheet reports.Click on OK to continue or Cancel to return."))

		{

			var url = "resetexporteddatalist.php";

			var rtype='Accounting_ExportedTimeSheets';

			var content = "emplist="+chkVals;

			DynCls_Ajax_result(url,rtype,content,'reloadMyGrid()');

		}

	}

}






function reloadMyGrid()
{
	var response=DynCls_Ajx_responseTxt;
	if(response == 1)
	{     
            //Timesheet Grid optimization
            console.log(window.location.href);
            if(window.location.href.indexOf('/BSOS/Accounting/Time_Mngmt/exportedtimesheets.php') > 0){
               window.location.href = '/BSOS/Accounting/Time_Mngmt/exportedtimesheets.php';
            }else{
		doGridSearch('search');// refresh grid if employee(s) are resetted...
            }
	}
}
function mainChkBox_ProcessedRecords()
{
	var chkObj=document.getElementById("chk");
	if(chkObj.checked==true)
		checkAll_ProcessedRecords();
	else
		clearAll_ProcessedRecords();
}
function clearAll_ProcessedRecords()
{
	var e = document.getElementsByName("auids[]");
	for (var i=0; i < e.length; i++)
		if (e[i].name == "auids[]")
			e[i].checked = false;
}

function checkAll_ProcessedRecords()
{
	var e = document.getElementsByName("auids[]");
	for (var i=0; i < e.length; i++)
		e[i].checked = true;
}
function chk_clear_mainChkbox()
{
	var e = document.getElementsByName("auids[]");
	for (var i=0; i < e.length; i++)
	{
		if (e[i].name == "auids[]")
		{

			if (e[i].checked == false)
			{
				document.getElementById("chk").checked=false;
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
				document.getElementById("chk").checked=true;
                return;
             }
        }
    }
}

//Function for approving timesheets from maingrid.
function doApproveMainTimeSheets()
{
	
	var links = document.getElementsByTagName('a');
	for(var a in links) {
	if(links[a].innerHTML == 'Update Status') 
	links[a].setAttribute("href", "javascript:void(0)");
	}

	
    form=document.forms[0];
    var numAddrs = numSelected_TimeSheet();
    var valAddrs = valSelected_TimeSheet();

    document.getElementById('getempusername').value = valAddrs;
	document.getElementById('getApproveStatus').value = 'frmMainGrid';
    if (numAddrs < 0)
    {
    	alert("There are no Employees to update the status of Timesheets.");
	var links = document.getElementsByTagName('a');
    for(var a in links) {
   if(links[a].innerHTML == 'Update Status') 
            links[a].setAttribute("href", "javascript:doApproveMainTimeSheets()");
    }
    	return;
    }
    else if (!numAddrs)
    {
    	alert("You have to select atleast one Employee to update the status of Timesheets from the Available Employees.");
		var links = document.getElementsByTagName('a');
    for(var a in links) {
   if(links[a].innerHTML == 'Update Status') 
            links[a].setAttribute("href", "javascript:doApproveMainTimeSheets()");
    }
    	return;
    }
	
	popupTimeSheetMessage();
	display_Dynmic_Message('UpdTimeStatus','cancel','updatestatus','','UpdTimeStatus_Chk');
}

function UpdTimeStatus_Chk(retstatus)
{
	switch(retstatus)
	{
		case 'cancel': clearAll_TimeSheet(); chk_clear_mainChkbox();

		var links = document.getElementsByTagName('a');
		for(var a in links) {
		if(links[a].innerHTML == 'Update Status') 
		links[a].setAttribute("href", "javascript:doApproveMainTimeSheets()");
		}
	    break;
		
		case 'updatestatus': 
		
		var getResStat = updateStatusToTime(0); if(getResStat == true) {  updateSelectedTimeStatus(); } break;
	}    
}

function updateSelectedTimeStatus()
{
	var radioVal = getRadValue();
	var form=document.forms[0];
	closeMessage();
	if(radioVal == "approve")
	{
		form.aa.value="approve";
		form.details.value=document.getElementById("getnotes").value;
		form.action="/BSOS/Accounting/Time_Mngmt/approvefax.php";
	}
	else if(radioVal == "reject")
	{
		form.aa.value="reject";
		form.details.value=document.getElementById("getnotes").value;
		form.action="/BSOS/Accounting/Time_Mngmt/rejectfax.php";
	}
	form.submit();
}

function updateStatusToTime(getStat)
{
	var radioVal = getRadValue();	
	if(radioVal == "")
	{
		if(getStat==0)
			alert("Please select a option to Approve / Reject TimeSheet(s) for Employee(s).");
		else
			alert("Please select a option to Approve / Reject TimeSheet(s).");
		return false;
	}
	else if(radioVal == "reject" && document.getElementById("getnotes").value == "")
	{
		alert("You are required to enter the notes while rejecting Timesheet(s). Please enter the Notes.");
		document.getElementById("getnotes").focus();
		return false;
	}
	return true;
}

function getRadValue()
{
	var val="";
	var radios = document.getElementsByName('UpdStatus');
	for(i=0;i<radios.length;i++)	
	{
		if(radios[i].checked)
		{
			val = radios[i].value;
		}
	}
	return val;
}

function doApproveTimeGrid()
{
	var links = document.getElementsByTagName('a');

	for(var a in links)
	{
		if(links[a].innerHTML == 'Update Status') 
			links[a].setAttribute("href", "javascript:void(0)");
	}

    form=document.forms[0];
    var numAddrs = numSelected_TimeSheet();
    var valAddrs = valSelected_TimeSheet();

	document.getElementById('Par_Timesheet_Val').value = valAddrs;
	if(document.getElementById('Approve_Status').value=="")
		document.getElementById('Approve_Status').value = "frmDoubleClk";

    if (numAddrs < 0)
    {
    	alert("There are no Timesheets to update the status.");
		var links = document.getElementsByTagName('a');
		for(var a in links)
		{
			if(links[a].innerHTML == 'Update Status') 
				links[a].setAttribute("href", "javascript:doApproveTimeGrid()");
		}
    	return;
    }
    else if(!numAddrs)
    {
    	alert("You have to select atleast one Timesheet to update the status from the Available Timesheets.");
		var links = document.getElementsByTagName('a');
		for(var a in links)
		{
			if(links[a].innerHTML == 'Update Status') 
				links[a].setAttribute("href", "javascript:doApproveTimeGrid()");
		}
    	return;
    }
	
	popupTimeSheetMessage();
	display_Dynmic_Message('UpdTimeStatus','cancel','updatestatus','','UpdTimeStatus_ChkGrid');
}

function UpdTimeStatus_ChkGrid(retstatus)
{
	switch(retstatus)
	{
		case 'cancel': clearAll_TimeSheet(); chk_clear_mainChkbox(); 
			var links = document.getElementsByTagName('a');
			for(var a in links)
			{
				if(links[a].innerHTML == 'Update Status') 
					links[a].setAttribute("href", "javascript:doApproveTimeGrid()");
			}
		break;
		case 'updatestatus': 
			var getResStat = updateStatusToTime(1); if(getResStat == true) {  updateSelectedTimeStatus(); } 
		break;
	}    
}

function doApproveTimeGridS(parid,frm)
{
	var links = document.getElementsByTagName('a');
	for(var a in links) {
	if(links[a].innerHTML == 'Update Status') 
	links[a].setAttribute("href", "javascript:doApproveMainTimeSheets()");
	}

	form=document.forms[0];
    sdates=form.sdatets.value.split("|");
	tscount=form.stscount.value.split("|");

	
    if(frm == 'cico'){
        var chkboxCount = '1';
        form.chkedrows.value = form.ts_sno.value;
    }else{
        var chkboxCount = form.chkcount.value;
        form.chkedrows.value = getCheckedStringAppr(chkboxCount);
    }

	if (form.chkedrows.value == '') 
	{
		alert("You have to select atleast one Timesheet to update the status from the Available Timesheets.");
		var links = document.getElementsByTagName('a');
		for(var a in links) {
			if(links[a].innerHTML == 'Update Status') 
			links[a].setAttribute("href", "javascript:doApproveMainTimeSheets()");
		}
		return;
	}

	popupTimeSheetMessage();
	display_Dynmic_Message('UpdTimeStatus','cancel','updatestatus','','UpdTimeStatus_ChkGridS');
}

function UpdTimeStatus_ChkGridS(retstatus)
{
	switch(retstatus)
	{
		case 'cancel': 
			var links = document.getElementsByTagName('a');
			for(var a in links) {
				if(links[a].innerHTML == 'Update Status') 
				links[a].setAttribute("href", "javascript:doApproveMainTimeSheets()");
			}
			break;
		
		case 'updatestatus':
			var getResStat = updateStatusToTime(1);
			if(getResStat == true) {				
				var form	= document.forms[0];
				var ts_snos_str = form.chkedrows.value;
				var ts_par_id 	= form.addr1.value;
				var content 	= "rtype=getTimesheetLockingStatus&ts_snos_str="+ts_snos_str+"&ts_par_id="+ts_par_id;
				var url = "/BSOS/Include/getAsgn.php";
				DynCls_Ajax_result(url,'rtype',content,"AjaxTimesheetLockingStatusCallBack()");
				return;
			}
			break;
	}    
}

function AjaxTimesheetLockingStatusCallBack()
{
	var AjaxRes=DynCls_Ajx_responseTxt.split("|");
	if(AjaxRes[0]==1)
	{
		alert('Timesheet has already been '+AjaxRes[1]+' by '+AjaxRes[2]+'.Please find this in '+AjaxRes[1]+' Timesheets.');
		window.opener.doGridSearch('search');
		window.close();
		return;
	}
	else{
		updateSelectedTimeStatus();
	}
}

function popupTimeSheetMessage()
{
	PopMsgHeadArr['UpdTimeStatus']='Update Status For TimeSheet(s)';
	PopMsgFLineArr['UpdTimeStatus']="";
	PopMsgQueArr['UpdTimeStatus']="";
	PopMsgSLineArr['UpdTimeStatus']="";
	PopMsgBtnTxtArr['updatestatus']='Update Status';
	PopMsgBtnValArr['updatestatus']='updatestatus';
	PopMsgExtMsgArr['UpdTimeStatus']="<table border=\"0\" cellpadding=\"2\" cellspacing=\"1\" width=\"98%\"><tr><td valign=\"center\" colspan=\"3\"></td></tr><tr><td valign=\"top\" align=\"left\" width=\"8%\"><INPUT type=radio value=\"approve\" name=\"UpdStatus\" id=\"UpdStatus\" checked=\"checked\"></td><td valign=\"center\" colspan=\"2\"><font size=\"2.0em\">Approve selected Employee(s) Timesheet(s)</font></td></tr><tr><td valign=\"top\" align=\"left\" width=\"8%\"><INPUT type=radio value=\"reject\" name=\"UpdStatus\" id=\"UpdStatus\"></td><td valign=\"center\" colspan=\"2\"><font size=\"2.0em\">Reject selected Employee(s) Timesheet(s)</font></td></tr><tr><td valign=\"center\" colspan=\"3\"></td></tr><tr><td valign=\"top\" align=\"left\" width=\"8%\">&nbsp;</td><td valign=\"top\"><font size=\"2.0em\">Notes :</font></td><td valign=\"center\"><textarea name=\"getnotes\" id=\"getnotes\" rows=\"3\" cols=\"30\" style=\"resize:none;\"></textarea></td></tr></table>";
}

function doDelPrint(id,invoice,rowAsgn)
{
	var v_heigth = window.screen.availHeight-50;
	var v_width  = window.screen.availWidth-10;
	
	var mode = "&mode=deleted";	
	path = "/include/tcpdf/print/print_timesheets.php?from=popup&addr1="+id+"&invoice="+invoice+"&rowAsgn="+rowAsgn+mode;
	remote=window.open(path,"timesheets","width="+v_width+"px,height="+v_heigth+"px,statusbar=yes,menubar=no,scrollbars=yes,left=0,top=0,dependent=yes,resizable=yes");
	remote.focus();
}

function doDeleteMainTimeSheet(sno)
{	
	numAddrs = numSelected_TimeSheet();
	valAddrs = valSelected_TimeSheet();
	if(document.getElementById('history').value=="no")
		var pageval = 1;
	else
		var pageval = 7;
		
	if (numAddrs < 0)
	{
		alert("There are no Timesheets available.");
		return;
	}
	else if (!numAddrs)
	{
		alert("You have to select atleast one timesheet to delete from the Available List.");
		return;
	}
	else
	{
		var content = "recordAction=timeMainDelete&getempusername="+valAddrs+"&sno="+sno+"&frompg="+pageval;
		var url = "/BSOS/Include/getMadisonStatus.php";
		DynCls_Ajax_result(url,'rtype',content,"getResponseForMainGridDelete()");
	}
}

function getResponseForMainGridDelete()
{
	var madisonDetailsArr = DynCls_Ajx_responseTxt.split("^");
	if(madisonDetailsArr[1] == "timeMainDelete")
	{
		if(madisonDetailsArr[0] == "Y")
		{
			alert("Some of selected employee(s) timesheet(s) have already been processed to send to Madison.\nTo Delete, please reset it from Accounting-Timesheets-Processed Timesheets.");
			return;
		}
		else
		{
			if(confirm("Are you sure you want to delete the selected employee(s) timesheet(s)?"))
			{
				var valAddrs = madisonDetailsArr[2];
				var sno = madisonDetailsArr[3];
				var frompg = madisonDetailsArr[4];
				window.location.href = "/include/deleteTimeSheet.php?getDelStatus=frmMainGrid&getempusername="+valAddrs+"&sno="+sno+"&frompg="+frompg;
			}
			else
			{
				document.forms[0].chk.checked=false;
				clearAll_TimeSheet();
			}
		}
	}
}
//Timesheet Grid optimization- added one param in url for (regular,custom,uom all type timesheets) to load the timesheets for default three months date range while editing and updating the status 
function doEdit(parid)
{
	var form = document.timesheet;
	form.action = "/include/new_timesheet.php?module=Accounting&ts_status=Approved&&mode=edit&sno="+parid+"&statusvalue=statapproved&frompage=approved&timeopttype=Approved";
	form.submit(); 
}
//Getting the uom_timesheet page when clicked on UOM Timesheet
//Timesheet Grid optimization- added one param in url for (regular,custom,uom all type timesheets) to load the timesheets for default three months date range while editing and updating the status 
function uomDoEdit(parid)
{
	var form = document.timesheet;
	form.action = "/include/uom_timesheet.php?module=Accounting&ts_status=Approved&&mode=edit&sno="+parid+"&statusvalue=statapproved&frompage=approved&timeopttype=Approved";
	form.submit(); 
}
function getResponseMadisonStatus()
{
	var madisonDetailsArr = DynCls_Ajx_responseTxt.split("^");
	if(madisonDetailsArr[1] == "timeEdit")
	{
		if(madisonDetailsArr[0] == "Y")
		{
			alert("This timesheet has already been processed to send to Madison.\nTo make changes, please reset it from Accounting-Timesheets-Processed Timesheets and approve again.");
			return;
		}
		else
		{
			form = document.forms[0];
	
			var ts_multiple = form.ts_multiple.value;	
			if(ts_multiple == 'Y')
				form.action = "/BSOS/Include/edittimemulti.php";
			else
				form.action = "edittime.php";
			form.submit();
		}
	}
}

function doApproveTimeGridSrej(parid,frm)
{
	
	var links = document.getElementsByTagName('a');
	for(var a in links) {
		if(links[a].innerHTML == 'Update Status') 
		links[a].setAttribute("href", "javascript:void(0)");
	}
	
	form=document.forms[0];
	sdates=form.sdatets.value.split("|");
	tscount=form.stscount.value.split("|");

     if(frm == 'cico'){
        var chkboxCount = '1';
        form.chkedrows.value = form.ts_sno.value;
        
    }else{
        var chkboxCount = form.chkcount.value;
        form.chkedrows.value = getCheckedStringAppr(chkboxCount);
    }
	
	if (form.chkedrows.value == '') 
	{
		alert("You have to select atleast one Timesheet to update the status from the Available Timesheets.");
	
		var links = document.getElementsByTagName('a');
		for(var a in links) {
		if(links[a].innerHTML == 'Update Status') 
		links[a].setAttribute("href", "javascript:doApproveMainTimeSheets()");
		}
		return;
	}
	
	popupTimeSheetMessagerej();
	display_Dynmic_Message('UpdTimeStatus','cancel','updatestatus','','UpdTimeStatus_ChkGridSrej');
}

function UpdTimeStatus_ChkGridSrej(retstatus)
{
	switch(retstatus)
	{
		case 'cancel': 
			var links = document.getElementsByTagName('a');
			for(var a in links)
				if(links[a].innerHTML == 'Update Status') 
					links[a].setAttribute("href", "javascript:doApproveMainTimeSheets()");
		break;
		case 'updatestatus': 
			var getResStat = updateStatusToTimerej(1); 
			if(getResStat == true)
			{
				var form	= document.forms[0];
				var ts_snos_str = form.chkedrows.value;
				var ts_par_id 	= form.addr1.value;
				var content 	= "rtype=getTimesheetLockingStatus&ts_snos_str="+ts_snos_str+"&ts_par_id="+ts_par_id+"&ts_status=Rejected";
				var url = "/BSOS/Include/getAsgn.php";
				DynCls_Ajax_result(url,'rtype',content,"AjaxTimesheetLockingStatusRejCallBack()");
				return;
			}
		break;
	}    
}

function AjaxTimesheetLockingStatusRejCallBack()
{
	var AjaxRes=DynCls_Ajx_responseTxt.split("|");
	if(AjaxRes[0]==1)
	{		
		alert('Timesheet has already been '+AjaxRes[1]+' by '+AjaxRes[2]+'.Please find this in '+AjaxRes[1]+' Timesheets.');
		window.opener.doGridSearch('search');
		window.close();
		return;
	}
	else{
		updateSelectedTimeStatusrej();
	}
}

function updateSelectedTimeStatusrej()
{
	var radioVal = getRadValue();
	var form=document.forms[0];
	closeMessage();
	
	if(radioVal == "approve")
	{
		form.aa.value="approve";
		form.action="/BSOS/Accounting/Time_Mngmt/rejecttoapprovefax.php";
	}
	
	form.submit();
}

function updateStatusToTimerej(getStat)
{
	var radioVal = getRadValue();	
	if(radioVal == "")
	{
		if(getStat==0)
			alert("Please select a option to Approve / Reject TimeSheet(s) for Employee(s).");
		else
			alert("Please select a option to Approve / Reject TimeSheet(s).");
		return false;
	}
	else if(radioVal == "reject" && document.getElementById("getnotes").value == "")
	{
		alert("You are required to enter the notes while rejecting Timesheet(s). Please enter the Notes.");
		document.getElementById("getnotes").focus();
		return false;
	}
	return true;
}

function popupTimeSheetMessagerej()
{
	PopMsgHeadArr['UpdTimeStatus']='Update Status For TimeSheet(s)';
	PopMsgFLineArr['UpdTimeStatus']="";
	PopMsgQueArr['UpdTimeStatus']="";
	PopMsgSLineArr['UpdTimeStatus']="";
	PopMsgBtnTxtArr['updatestatus']='Update Status';
	PopMsgBtnValArr['updatestatus']='updatestatus';
	PopMsgExtMsgArr['UpdTimeStatus']="<table border=\"0\" cellpadding=\"2\" cellspacing=\"1\" width=\"98%\"><tr><td valign=\"center\" colspan=\"3\"></td></tr><tr><td valign=\"top\" align=\"left\" width=\"8%\"><INPUT type=radio value=\"approve\" name=\"UpdStatus\" id=\"UpdStatus\" checked=\"checked\"></td><td valign=\"center\" colspan=\"2\"><font size=\"2.0em\">Approve selected Employee(s) Timesheet(s)</font></td></tr></table>";
}

function doCancelreject()
{
	document.location.href = "rejectedtimesheets.php";
}

function doFCancelReject()
{
    var form=document.forms[0];
    var val=form.addr.value;
    var t1=form.t1.value;
    var t2=form.t2.value;
	window.location.href="rejected_detailtimesheet.php?addr="+val;
}

function doApproveTimeGridrej()
{
    form=document.forms[0];
    var numAddrs = numSelected_TimeSheet();
    var valAddrs = valSelected_TimeSheet();

	document.getElementById('Par_Timesheet_Val').value = valAddrs;
    document.getElementById('Approve_Status').value = "frmDoubleClk";
    if (numAddrs < 0)
    {
    	alert("There are no Timesheets to update the status.");
    	return;
    }
    else if (!numAddrs)
    {
    	alert("You have to select atleast one Timesheet to update the status from the Available Timesheets.");
    	return;
    }
	
	popupTimeSheetMessagerej();
	display_Dynmic_Message('UpdTimeStatus','cancel','updatestatus','','UpdTimeStatus_ChkGridrej');
}

function UpdTimeStatus_ChkGridrej(retstatus)
{
	switch(retstatus)
	{
		case 'cancel': clearAll_TimeSheet(); chk_clear_mainChkbox(); break;
		case 'updatestatus': var getResStat = updateStatusToTimerej(1); if(getResStat == true) {  updateSelectedTimeStatusrej(); } break;
	}    
}

function doDeleteTimeSheetreject()
{	
	numAddrs = numSelected_TimeSheet();
	valAddrs = valSelected_TimeSheet();

	if(document.getElementById('history').value=="no")
		var pageval = 8;
	else
		var pageval = 9;

	if (numAddrs < 0)
	{
		alert("There are no Timesheets to delete.");
		return;
	}
	else if (!numAddrs)
	{
		alert("You have to select atleast one timesheet to delete from the Available List.");
		return;
	}
	else
	{
		var content = "recordAction=timeDelete&paraddr="+valAddrs+"&frompg="+pageval;
		var url = "/BSOS/Include/getMadisonStatus.php";
		DynCls_Ajax_result(url,'rtype',content,"getResponseForDelete()");
	}
}
function getResponseForDeletereject()
{
	var madisonDetailsArr = DynCls_Ajx_responseTxt.split("^");
	var servicedate = document.getElementById('servicedate').value;
	var servicedateto = document.getElementById('servicedateto').value;
	if(madisonDetailsArr[1] == "timeDelete")
	{
		if(madisonDetailsArr[0] == "Y")
		{
			alert("Some of timesheet(s) have already been processed to send to Madison.\nTo Delete, please reset it from Accounting-Timesheets-Processed Timesheets.");
			return;
		}
		else
		{
			if(confirm("Are you sure you want to delete the selected timesheet(s)?"))
			{
				var paraddr = madisonDetailsArr[2];
				var frompg = madisonDetailsArr[4];
				window.location.href = "/include/deleteTimeSheet.php?paraddr="+paraddr+"&frompg="+frompg+"&servicedate="+servicedate+"&servicedateto="+servicedateto;;
			}
			else
			{
				document.forms[0].chk.checked=false;
				clearAll_TimeSheet();
			}
		}
	}
}

/******* Timesheet Rules *********/

function validateRules(sobj,eobj)
{
	var re_date = /^\s*(\d{1,2})\/(\d{1,2})\/(\d{2,4})\s*$/;

	if(!re_date.exec(sobj.value))
	{
		alert("Invalid Date/Date Format.\n\nPlease enter date in (mm/dd/yyyy) format.");
		sobj.select();
		return false;
	}
	else if(!re_date.exec(eobj.value))
	{
		alert("Invalid Date/Date Format.\n\nPlease enter date in (mm/dd/yyyy) format.");
		eobj.select();
		return false;
	}
	
	re_date.exec(sobj.value);
	var smon = Number(RegExp.$1), sday = Number(RegExp.$2), syr = Number(RegExp.$3);
	
	re_date.exec(eobj.value);
	var emon = Number(RegExp.$1), eday = Number(RegExp.$2), eyr = Number(RegExp.$3);
	
	if((syr > eyr) || (syr == eyr && smon > emon) || (syr == eyr && smon == emon && sday > eday))
	{
		alert("End Date should be greater than or equal to Start Date");
		eobj.select();
		return false;
	}
	return true;
}

function verifyRulesDates()
{
	var form = document.timesheet;
	
	var sdate=form.txtrsdate.value;
	var edate=form.txtredate.value;
	var date1 = new Date(sdate);
	var date2 = new Date(edate);
	var timeDiff = Math.abs(date2.getTime() - date1.getTime());
	var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24)); 
	if(diffDays==6)
	{
		return true;
	}
	else
	{
		alert("You can apply timesheet rules for a week date range. Please select the start and end date for one week.");
		return false;
	}
}

function applyRules()
{
	form=document.timesheet;
	var numAddrs = numSelected_TimeSheet();
	var valAddrs = valSelected_TimeSheet();
        var servicedate = form.servicedate.value;
        var servicedateto = form.servicedateto.value;
        
        if (numAddrs < 0)
	{
		alert("There are no Timesheets to Process Rules.");
		return;
	}
	else if(!numAddrs)
	{
		alert("You have to select atleast one Timesheet to Process Rules from the Available Timesheets.");
		return;
	}else{
            var content = "mode=Timesheet_SearchOtherLayouts&paraddr="+valAddrs+"&frompg=empfaxhis&servicedate="+servicedate+"&servicedateto="+servicedateto;
            var url = "../../../include/functions.php";
            DynCls_Ajax_result(url,'rtype',content,"searchForOtherTimesheetLayouts()");
        }
}

function searchForOtherTimesheetLayouts(){
    var Timeresponse = DynCls_Ajx_responseTxt;
    if(Timeresponse=='yes'){
        alert("Process Rules Feature is only applicable to Regular Timesheet Layout.\nPlease select Regular Timesheets.");
        return;
    }else{
        form=document.timesheet;
	var valAddrs = valSelected_TimeSheet();
        form.addr.value = valAddrs;
        form.action = "createbatch.php";
        form.submit();  
    }
}

function delFromBatch()
{
	var form = document.timesheet;
	numAddrs = numSelected_TimeSheet();
	valAddrs = valSelected_TimeSheet();

	if (numAddrs < 0)
	{
		alert("There are no Timesheets to remove.");
	}
	else if(!numAddrs)
	{
		alert("You have to select atleast one timesheet to remove from the Available List.");
	}
	else
	{
		form.aa.value="deltime";
		form.action="navbatch.php";
		form.saddr.value=valAddrs;
		form.submit();
	}
}

function processRules()
{
	var form = document.timesheet;
	numAddrs = numSelected_TimeSheet();
	if (numAddrs < 0)
	{
		alert("There are no Timesheets to apply rules.");
	}
	else if(!numAddrs)
	{
		alert("You have to select atleast one timesheet from the Available List.");
	}
	else
	{
		var left = (window.document.body.clientWidth / 2) - 175;
		var top  = (window.document.body.clientHeight / 2) - 75;	

		var obj  = document.getElementById("divrules");
		var obj1 = document.getElementById("dynsndiv");

		if(navigator.appName == "Microsoft Internet Explorer")
		{
			with(obj1) 
			{
				style.width = window.document.body.clientWidth;
				style.height = window.document.body.clientHeight;
			}
		}
		obj1.style.display = "block";

		with(obj)
		{
			style.top = top+"px";
			style.left = left+"px";
			style.display = "block";	
		}
	}
}

function contRules(sts)
{
	var form = document.timesheet;
	var mobj = document.getElementById("dynsndiv");
	var sobj = document.getElementById("divrules");
	
	if(sts == "-1")
	{
		mobj.style.display = "none";
		sobj.style.display = "none";
	}

	if(sts == "1")
	{
		valAddrs = valSelected_TimeSheet();

		if(isNotEmpty(form.txtrsdate,"Start Date"))
		{
			mobj.style.display = "none";
			sobj.style.display = "none";
			form.saddr.value=valAddrs;
			form.action = "applyrules.php";
			form.submit();
		}
	}
}

function asgnOTDTSelected()
{
	var form = document.timesheet;

	var flag = true;
	var e = document.getElementsByName("auids[]");
	var o = document.getElementsByName("otids[]");
	var otVal="";

	for(var i=0; i < e.length; i++)
	{
		if(e[i].checked == true)
		{
			if(o[i].value=="")
			{
				flag=false;
				alert("Please select OT/DT Assignment");
				break;
			}
			else
			{
				otVal=otVal+","+o[i].value;
			}
		}
	}
	form.otasgn.value=otVal;
	return flag;
}

function gridOTDTSelected(rowid)
{
	var form = document.timesheet;
	var flag = true;

	var o = document.getElementsByName("otids[]");

	otVal=o[rowid].value;

	if(otVal=="")
	{
		flag=false;
		alert("Please select OT/DT Assignment");
	}

	form.otasgn.value=otVal;
	return flag;
}

function remFromBatch()
{
	var form = document.sheet;

	form.aa.value="remtime";
	form.action="navbatch.php";
	form.saddr.value=form.euser.value;
	form.submit();
}

function saveRule()
{
	var form = document.sheet;
	form.action="applyrule.php";
	form.submit();
}

function deleteRulesTS()
{
	var servicedate = document.getElementById('servicedate').value;
	var servicedateto = document.getElementById('servicedateto').value;

	numAddrs = numSelected_TimeSheet();
	valAddrs = valSelected_TimeSheet();

	if (numAddrs < 0)
	{
		alert("There are no Timesheets to delete.");
		return;
	}
	else if (!numAddrs)
	{
		alert("You have to select atleast one timesheet to delete from the Available List.");
		return;
	}
	else
	{
		if(confirm("Are you sure you want to delete the selected timesheet(s)?"))
		{
			var frompg = 10;
			window.location.href = "/include/deleteTimeSheet.php?paraddr="+valAddrs+"&frompg="+frompg+"&servicedate="+servicedate+"&servicedateto="+servicedateto;
		}
	}
}

function deleteAppliedTS()
{
	var servicedate = document.getElementById('servicedate').value;
	var servicedateto = document.getElementById('servicedateto').value;

	numAddrs = numSelected_TimeSheet();
	valAddrs = valSelected_TimeSheet();

	if (numAddrs < 0)
	{
		alert("There are no Timesheets to delete.");
		return;
	}
	else if (!numAddrs)
	{
		alert("You have to select atleast one timesheet to delete from the Available List.");
		return;
	}
	else
	{
		if(confirm("Are you sure you want to delete the selected timesheet(s)?"))
		{
			var frompg = 11;
			window.location.href = "/include/deleteTimeSheet.php?paraddr="+valAddrs+"&frompg="+frompg+"&servicedate="+servicedate+"&servicedateto="+servicedateto;
		}
	}
}

//Function to get custom_timesheet page
//Timesheet Grid optimization- added one param in url for (regular,custom,uom all type timesheets) to load the timesheets for default three months date range while editing and updating the status 
function customDoEdit(parid)
{
    var form = document.timesheet;
    form.action = "/include/custom_timesheet.php?module=Accounting&ts_status=Approved&&mode=edit&sno="+parid+"&statusvalue=statapproved&frompage=approved&timeopttype=Approved";
    form.submit(); 
}

//Function to get custom window
function openwin_custom(val, sno)
{
	var v_heigth = 600;
	var v_width  = 1200;
	var left1=(window.screen.availWidth-v_width)/2;
	var top1=(window.screen.availHeight-v_heigth)/2;
	var module = document.getElementById('module');
	var module_name='';
	if(module !=null){
		module_name= document.getElementById('module').value;
	}
	//-- siva prasanth
    remote=window.open("/BSOS/Accounting/Time_Mngmt/timesheet1.php?date="+val+"&sno="+sno+"&ts_type=Custom&module="+module_name,"","width="+v_width+"px,height="+v_heigth+"px,statusbar=no,menubar=no,scrollbars=yes,left="+left1+"px,top="+top1+"px,dependent=yes,resizable=no");
	remote.focus();
}


function doProcessConflictTimesheets()
{
	form=document.timesheet;
	var numAddrs = numSelected_TimeSheet();
	var valAddrs = valSelected_TimeSheet();
        var servicedate = form.servicedate.value;
        var servicedateto = form.servicedateto.value;
        if (numAddrs < 0)
	{
		alert("There are no Conflicted Timesheets to Process.");
		return;
	}
	else if(!numAddrs)
	{
		alert("You have to select atleast one to Process.");
		return;
	}else{
            var content = "mode=Timesheet_searchForNonconflicts&paraddr="+valAddrs+"&frompg=contimesheets&servicedate="+servicedate+"&servicedateto="+servicedateto;
            var url = "../../../BSOS/Accounting/Time_Mngmt/getConflictedTimesheets.php";
            DynCls_Ajax_result(url,'rtype',content,"searchForNonconflictTimesheets()");
        }
}

function searchForNonconflictTimesheets(){
    var Timeresponse = DynCls_Ajx_responseTxt;
    if(Timeresponse=='yes'){
        alert("Please select only records with process status of Conflict");
        return;
    }else{
        form=document.timesheet;
	var valAddrs = valSelected_TimeSheet();
        form.saddr.value = valAddrs;
        form.action = "processConflicts.php";
        form.submit();  
    }
}