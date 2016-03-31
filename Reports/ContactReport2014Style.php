<?php
/*******************************************************************************
*
*  filename    : Reports/DirectoryReport.php
*  last change : 2003-08-30
*  description : Creates a Member directory
*
*  http://www.churchdb.org/
*  Copyright 2003  Jason York, 2004-2005 Michael Wilt, Richard Bondi
*
*  ChurchInfo is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
******************************************************************************/

require '../Include/Config.php';
require '../Include/Functions.php';
require '../Include/ReportFunctions.php';
require '../Include/ReportConfig.php';

// Check for Create Directory user permission.
if (!$bCreateDirectory) {
    Redirect('Menu.php');
    exit;
}

class Person {
  public $Name;
  public $ChineseName;
  public $Email;
  public $Phone;
}

class AddressPhone {
  public $Address;
  public $Phone;
}

class PDF_Directory extends ChurchInfoReport {

    // Private properties
    var $_Margin_Left = 16;        // Left Margin
    var $_Margin_Top  = 0;         // Top margin 
    var $_Char_Size   = 10;        // Character size
    var $_Column      = 0;
    var $_Font        = 'Times';
    var $_Gutter      = 5;
    var $_LS          = 4;
    var $sFamily;
    var $sLastName;
    var $_ColWidth    = 58;
    var $_Custom;
    var $_NCols       = 3;
    var $_PS          = 'Letter';
    var $sSortBy = "";

    function Header()
    {
        global $bDirUseTitlePage;

        if (($this->PageNo() > 1) || ($bDirUseTitlePage == false))
        {
            //Select Arial bold 15
            $this->SetFont($this->_Font,'B',15);
            //Line break
            $this->Ln(7);
            //Move to the right
            $this->SetX($this->_Margin_Left);
            //Framed title
            $this->Cell($this->w - ($this->_Margin_Left*2),10,$this->sChurchName . " - " . gettext("Directory"),1,0,'C');
            $this->SetY(25);
        }
    }

    function Footer()
    {
        global $bDirUseTitlePage;

        if (($this->PageNo() > 1) || ($bDirUseTitlePage == false))
        {
            //Go to 1.7 cm from bottom
            $this->SetY(-17);
            //Select Arial italic 8
            $this->SetFont($this->_Font,'I',8);
            //Print centered page number
            $iPageNumber = $this->PageNo();
            if ($bDirUseTitlePage)
                $iPageNumber--;
            $this->Cell(0,10, gettext("Page") . " " . $iPageNumber."    ".date("M d, Y g:i a",time()),0,0,'C');
        }
    }

    function TitlePage()
    {
        global $sChurchName;
        global $sDirectoryDisclaimer;
        global $sChurchAddress;
        global $sChurchCity;
        global $sChurchState;
        global $sChurchZip;
        global $sChurchPhone;

        //Select Arial bold 15
        $this->SetFont($this->_Font,'B',15);

        if (is_readable($this->bDirLetterHead))
            $this->Image($this->bDirLetterHead,10,5,190);

        //Line break
        $this->Ln(5);
        //Move to the right
        $this->MultiCell(197,10,"\n\n\n". $sChurchName . "\n\n" . gettext("Directory") . "\n\n",0,'C');
        $this->Ln(5);
        $today = date("F j, Y");
        $this->MultiCell(197,10,$today . "\n\n",0,'C');

        $sContact = sprintf("%s\n%s, %s  %s\n\n%s\n\n", $sChurchAddress, $sChurchCity, $sChurchState, $sChurchZip, $sChurchPhone);
        $this->MultiCell(197,10,$sContact,0,'C');
        $this->Cell(10);
        $this->MultiCell(197,10,$sDirectoryDisclaimer,0,'C');
        $this->AddPage();
    }


    // Sets the character size
    // This changes the line height too
    function Set_Char_Size($pt) {
        if ($pt > 3) {
            $this->_Char_Size = $pt;
            $this->SetFont($this->_Font,'',$this->_Char_Size);
        }
    }

    // Constructor
    function PDF_Directory($nc=1, $paper='letter', $fs=10, $ls=4) {
//        parent::FPDF("P", "mm", $this->paperFormat);
	parent::FPDF("P", "mm", $paper);
	$this->_Char_Size = $fs;
	$this->_LS = $ls;

        $this->_Column      = 0;
        $this->_Font        = "Times";
        $this->SetMargins(0,0);
        $this->Open();
        $this->Set_Char_Size($this->_Char_Size);
        $this->SetAutoPageBreak(false);

        $this->_Margin_Left = 13;
        $this->_Margin_Top  = 13;
        $this->_Custom = array();
	$this->_NCols = $nc;
	$this->_ColWidth = 190 / $nc - $this->_Gutter;
    }

    function AddCustomField($order, $use){
        $this->_Custom[$order] = $use;
    }
    
    function NbLines($w,$txt){
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb)
        {
            $c=$s[$i];
            if($c=="\n")
            {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }

    function Check_Lines($numlines, $fid, $pid)
    {
        // Need to determine if we will extend beyoned 17mm from the bottom of
        // the page.
        
        $h = 0; // check image height.  id will be zero if not included
           $famimg = "../Images/Family/".$fid.".jpg";
        if (file_exists($famimg)) 
        {
            $s = getimagesize($famimg);
            $h = ($this->_ColWidth / $s[0]) * $s[1];
        }

           $persimg = "../Images/Person/".$pid.".jpg";
        if (file_exists($persimg)) 
        {
            $s = getimagesize($persimg);
            $h = ($this->_ColWidth / $s[0]) * $s[1];
        }


//      if ($this->GetY() + $h + $numlines * 5 > $this->h - 27)  
        if ($this->GetY() + $h + $numlines * $this->_LS > $this->h - 27)
        {
            // Next Column or Page
            if ($this->_Column == $this->_NCols-1)
            {
                $this->_Column = 0;
                $this->SetY(25);
                $this->AddPage();
            }
            else 
            {
                $this->_Column++;
                $this->SetY(25);
            }
        }
    }

    // This function prints out the heading when a letter
    // changes.
    function Add_Header($sLetter)
    {
        $this->Check_Lines(2, 0, 0);
        $this->SetTextColor(0);
        $this->SetFont($this->_Font,'B',$this->_Char_Size);
        $_PosX = ($this->_Column*($this->_ColWidth+$this->_Gutter)) + $this->_Margin_Left;
        $_PosY = $this->GetY();
        $this->SetXY($_PosX, $_PosY);
        $this->Cell($this->_ColWidth * 2 / 3, $this->_LS, $sLetter, "B",1,"L",0);
        
        // restore color
        $this->SetTextColor(0);
        $this->SetFont($this->_Font,'',$this->_Char_Size);
        $this->SetY($this->GetY() + $this->_LS);
    }

    function sGetCustomString($rsCustomFields, $aRow){
        $numCustomFields = mysql_num_rows($rsCustomFields);
        if ($numCustomFields > 0) {
            extract($aRow);
            $sSQL = "SELECT * FROM person_custom WHERE per_ID = " . $per_ID;
            $rsCustomData = RunQuery($sSQL);
            $aCustomData = mysql_fetch_array($rsCustomData, MYSQL_BOTH);
            $numCustomData = mysql_num_rows($rsCustomData);
            mysql_data_seek($rsCustomFields,0);
            $OutStr = ""; 
            while ( $rowCustomField = mysql_fetch_array($rsCustomFields, MYSQL_BOTH) ){
                extract($rowCustomField);
                $sCustom = "bCustom".$custom_Order;
                if($this->_Custom[$custom_Order]){
                	
                	$currentFieldData = displayCustomField($type_ID, $aCustomData[$custom_Field], $custom_Special);
                	
//                    $currentFieldData = trim($aCustomData[$custom_Field]);
                    if($currentFieldData != ""){
                        $OutStr .= "   " . $custom_Name . ": " . $currentFieldData .= "\n";
                    }
                }
            }
            return $OutStr;
        }else{
            return "";
        }
        
    }

    // This function formats the string for the address phone info
    function pGetAddressPhone( $aRow )
    {
        extract($aRow);
        
        $addrPhone = new AddressPhone();

        $addr = "";
	      if (strlen($fam_Address1)) { $addr .= $fam_Address1;}
	      if (strlen($fam_Address2)) { $addr .= "  ".$fam_Address2;}
        if (strlen($fam_City)) { $addr .= "  " . $fam_City . ", " . $fam_State . " " . $fam_Zip . "\n";  }
        $addrPhone->Address = $addr;

        $addrPhone->Phone = "";
        if (strlen($fam_WorkPhone)) {
            $addrPhone->Phone = ExpandPhoneNumber($fam_WorkPhone, $fam_Country, $bWierd);
        }
        if (strlen($fam_HomePhone)) {
            $addrPhone->Phone = ExpandPhoneNumber($fam_HomePhone, $fam_Country, $bWierd);
        }
        if (strlen($fam_CellPhone)) {
            $addrPhone->Phone = ExpandPhoneNumber($fam_CellPhone, $fam_Country, $bWierd);
        }

        return $addrPhone;
    }
    
    function addName($aRow)
    {
        extract($aRow);
        if(strlen($this->sRecordName)) {
            $this->sRecordName .= "\n";
        }
        $this->sRecordName .= $per_FirstName . " " . $per_LastName;
    }

    // This function formats the string for the head of household.
    // NOTE: This is used for the Head AND Spouse (called twice)
    function pGetPerson($rsCustomFields, $aHead )
    {
        extract($aHead);

        $pHead = new Person();
        
        $pHead->Name = $per_FirstName . " " . $per_LastName;

        $sCountry = SelectWhichInfo($per_Country,$fam_Country,false);
        if (strlen($per_WorkPhone)) {
            $pHead->Phone = ExpandPhoneNumber($per_WorkPhone, $sCountry, $bWierd);
        }
        if (strlen($per_HomePhone)) {
            $pHead->Phone = ExpandPhoneNumber($per_HomePhone, $sCountry, $bWierd);
        }
        if (strlen($per_CellPhone)) {
            $pHead->Phone = ExpandPhoneNumber($per_CellPhone, $sCountry, $bWierd);
        }
        
        if (strlen($per_WorkEmail)) $pHead->Email = $per_WorkEmail;
        if (strlen($per_Email)) $pHead->Email = $per_Email;
        
        // TODO(ferryzhou): add chinese name from custom field.
        //$sHeadStr .= $this->sGetCustomString($rsCustomFields, $aHead);

        return $pHead;
    }

    // Number of lines is only for the $text parameter
    function Add_Record($persons, $addrPhone, $numlines, $fid, $pid)
    {
        $this->Check_Lines($numlines, $fid, $pid);

        foreach ($persons as $person) {
          $this->Print_Person($person);
        }

        $_PosX = ($this->_Column*($this->_ColWidth+$this->_Gutter)) + $this->_Margin_Left;
        $_PosY = $this->GetY();

        $this->SetXY($_PosX + $this->_ColWidth/10, $_PosY);
        $this->MultiCell($this->_ColWidth, $this->_LS, iconv("UTF-8","ISO-8859-1",$addrPhone->Address), 0, 'L');
        $this->SetXY($_PosX, $_PosY);
        $this->MultiCell($this->_ColWidth, $this->_LS, iconv("UTF-8","ISO-8859-1",$addrPhone->Phone), 0, 'R');
        $this->SetY($this->GetY() + $this->_LS);
    }
    
    // This prints the family name in BOLD
    function Print_Person($person)
    {
        $_PosX = ($this->_Column*($this->_ColWidth+$this->_Gutter)) + $this->_Margin_Left;
        $_PosY = $this->GetY();
        $this->SetFont($this->_Font,'B',$this->_Char_Size);
        $this->SetXY($_PosX, $_PosY);
        $this->MultiCell($this->_ColWidth, $this->_LS, $person->Name, 0, 'L');
        
        $this->SetFont($this->_Font,'',$this->_Char_Size);
        $this->SetXY($_PosX + $this->_ColWidth/4, $_PosY);
        $this->MultiCell($this->_ColWidth, $this->_LS, $person->Name . " " . $person->Email);
        
        $this->SetFont($this->_Font,'',$this->_Char_Size);
        $this->SetXY($_PosX, $_PosY);
        $this->MultiCell($this->_ColWidth, $this->_LS, $person->Phone, 0, 'R');
        $this->SetY($this->GetY() + $this->_LS);
    }
}

// Get and filter the classifications selected
$count = 0;
if(array_key_exists ("sDirClassifications", $_POST) and $_POST["sDirClassifications"] != "")
{
    foreach ($_POST["sDirClassifications"] as $Cls)
    {
        $aClasses[$count++] = FilterInput($Cls,'int');
    }
    $sDirClassifications = implode(",",$aClasses);
}
else
{
    $sDirClassifications = "";
}
$count = 0;
foreach ($_POST["sDirRoleHead"] as $Head)
{
    $aHeads[$count++] = FilterInput($Head,'int');
}
$sDirRoleHeads = implode(",",$aHeads);

$count = 0;
foreach ($_POST["sDirRoleSpouse"] as $Spouse)
{
    $aSpouses[$count++] = FilterInput($Spouse,'int');
}
$sDirRoleSpouses = implode(",",$aSpouses);

$count = 0;
foreach ($_POST["sDirRoleChild"] as $Child)
{
    $aChildren[$count++] = FilterInput($Child,'int');
}

// Get other settings
$bDirAddress = isset($_POST["bDirAddress"]);
$bDirWedding = isset($_POST["bDirWedding"]);
$bDirBirthday = isset($_POST["bDirBirthday"]);
$bDirFamilyPhone = isset($_POST["bDirFamilyPhone"]);
$bDirFamilyWork = isset($_POST["bDirFamilyWork"]);
$bDirFamilyCell = isset($_POST["bDirFamilyCell"]);
$bDirFamilyEmail = isset($_POST["bDirFamilyEmail"]);
$bDirPersonalPhone = isset($_POST["bDirPersonalPhone"]);
$bDirPersonalWork = isset($_POST["bDirPersonalWork"]);
$bDirPersonalCell = isset($_POST["bDirPersonalCell"]);
$bDirPersonalEmail = isset($_POST["bDirPersonalEmail"]);
$bDirPersonalWorkEmail = isset($_POST["bDirPersonalWorkEmail"]);
$bDirPhoto = isset($_POST["bDirPhoto"]);

$sChurchName = FilterInput($_POST["sChurchName"]);
$sDirectoryDisclaimer = FilterInput($_POST["sDirectoryDisclaimer"]);
$sChurchAddress = FilterInput($_POST["sChurchAddress"]);
$sChurchCity = FilterInput($_POST["sChurchCity"]);
$sChurchState = FilterInput($_POST["sChurchState"]);
$sChurchZip = FilterInput($_POST["sChurchZip"]);
$sChurchPhone = FilterInput($_POST["sChurchPhone"]);

$bDirUseTitlePage = isset($_POST["bDirUseTitlePage"]);


$bNumberofColumns = FilterInput($_POST["NumCols"]);
$bPageSize = FilterInput($_POST["PageSize"]);
$bFontSz = FilterInput($_POST["FSize"]);
$bLineSp = $bFontSz / 3 ;
if($bPageSize=="letter")$bPageSize="letter"; else $bPageSize="legal";
//echo "ncols={$bNumberofColumns}  page size={$bPageSize}";


// Instantiate the directory class and build the report.
//echo "font sz = {$bFontSz} and line sp={$bLineSp}";
$pdf = new PDF_Directory($bNumberofColumns, $bPageSize, $bFontSz, $bLineSp);

// Get the list of custom person fields
$sSQL = "SELECT person_custom_master.* FROM person_custom_master ORDER BY custom_Order";
$rsCustomFields = RunQuery($sSQL);
$numCustomFields = mysql_num_rows($rsCustomFields);

if ($numCustomFields > 0) {
    while ( $rowCustomField = mysql_fetch_array($rsCustomFields, MYSQL_ASSOC) ){ 
        $pdf->AddCustomField($rowCustomField['custom_Order']
                            , isset($_POST["bCustom".$rowCustomField['custom_Order']])
                            );
    }
}

// Read in report settings from database
$rsConfig = mysql_query("SELECT cfg_name, IFNULL(cfg_value, cfg_default) AS value FROM config_cfg WHERE cfg_section='ChurchInfoReport'");
if ($rsConfig) {
    while (list($cfg_name, $cfg_value) = mysql_fetch_row($rsConfig)) {
        $pdf->$cfg_name = $cfg_value;
    }
}

$pdf->AddPage();

if ($bDirUseTitlePage) $pdf->TitlePage();

$sClassQualifier = ""; 
if (strlen($sDirClassifications)) $sClassQualifier = "AND per_cls_ID in (" . $sDirClassifications . ")";

$sWhereExt = "";
if (!empty($_POST["GroupID"]))
{
    $sGroupTable = "(person_per, person2group2role_p2g2r)";

    $count = 0;
    foreach ($_POST["GroupID"] as $Grp)
    {
        $aGroups[$count++] = FilterInput($Grp,'int');
    }
    $sGroupsList = implode(",",$aGroups);

    $sWhereExt .= "AND per_ID = p2g2r_per_ID AND p2g2r_grp_ID in (" . $sGroupsList . ")";

    // This is used by per-role queries to remove duplicate rows from people assigned multiple groups.
    $sGroupBy = " GROUP BY per_ID";
} else { 
	$sGroupTable = "person_per";
	$sGroupsList = "";
	$sWhereExt = ""; 
	$sGroupBy = "";
}

if (array_key_exists ('cartdir', $_POST))
{
    $sWhereExt .= "AND per_ID IN (" . ConvertCartToString($_SESSION['aPeopleCart']) . ")";
}

$mysqlinfo = mysql_get_server_info();
$mysqltmp = explode(".", $mysqlinfo);
$mysqlversion = $mysqltmp[0];
if(count($mysqltmp[1] > 1)) 
    $mysqlsubversion = $mysqltmp[1]; 
    else $mysqlsubversion = 0;
if($mysqlversion >= 4){
    // This query is similar to that of the CSV export with family roll-up.
    // Here we want to gather all unique families, and those that are not attached to a family.
    $sSQL = "(SELECT *, 0 AS memberCount, per_LastName AS SortMe FROM $sGroupTable LEFT JOIN family_fam ON per_fam_ID = fam_ID WHERE per_fam_ID = 0 $sWhereExt $sClassQualifier )
        UNION (SELECT *, COUNT(*) AS memberCount, per_LastName AS SortMe FROM $sGroupTable LEFT JOIN family_fam ON per_fam_ID = fam_ID WHERE per_fam_ID > 0 $sWhereExt $sClassQualifier  GROUP BY per_fam_ID HAVING memberCount = 1)
        UNION (SELECT *, COUNT(*) AS memberCount, per_LastName AS SortMe FROM $sGroupTable LEFT JOIN family_fam ON per_fam_ID = fam_ID WHERE per_fam_ID > 0 $sWhereExt $sClassQualifier  GROUP BY per_fam_ID HAVING memberCount > 1)
        ORDER BY SortMe";
}else if($mysqlversion == 3 && $mysqlsubversion >= 22){
    // If UNION not supported use this query with temporary table.  Prior to version 3.22 no IF EXISTS statement.
    $sSQL = "DROP TABLE IF EXISTS tmp;";
    $rsRecords = mysql_query($sSQL) or die(mysql_error());
    $sSQL = "CREATE TABLE tmp TYPE = MyISAM SELECT *, 0 AS memberCount, per_LastName AS SortMe FROM $sGroupTable LEFT JOIN family_fam ON per_fam_ID = fam_ID WHERE per_fam_ID = 0 $sWhereExt $sClassQualifier ;"; 
    $rsRecords = mysql_query($sSQL) or die(mysql_error());
    $sSQL = "INSERT INTO tmp SELECT *, COUNT(*) AS memberCount, fam_Name AS SortMe FROM $sGroupTable LEFT JOIN family_fam ON per_fam_ID = fam_ID WHERE per_fam_ID > 0 $sWhereExt $sClassQualifier GROUP BY per_fam_ID HAVING memberCount = 1;"; 
    $rsRecords = mysql_query($sSQL) or die(mysql_error());
    $sSQL = "INSERT INTO tmp SELECT *, COUNT(*) AS memberCount, fam_Name AS SortMe FROM $sGroupTable LEFT JOIN family_fam ON per_fam_ID = fam_ID WHERE per_fam_ID > 0 $sWhereExt $sClassQualifier GROUP BY per_fam_ID HAVING memberCount > 1;";
    $rsRecords = mysql_query($sSQL) or die(mysql_error());
    $sSQL = "SELECT DISTINCT * FROM tmp ORDER BY SortMe";

}else{
    die(gettext("This option requires at least version 3.22 of MySQL!  Hit browser back button to return to ChurchInfo."));
}

$rsRecords = RunQuery($sSQL);

// This is used for the headings for the letter changes.
// Start out with something that isn't a letter to force the first one to work
$sSectionWord = "0";

while ($aRow = mysql_fetch_array($rsRecords))
{
    extract($aRow);
    
    $addrPhone = $pdf->pGetAddressPhone($aRow);
    
    $pdf->sSortBy = $SortMe;
    
    $isFamily = false;

    $persons = array();
    if ($memberCount > 1) // Here we have a family record.
    {
        $iFamilyID = $per_fam_ID;
        $isFamily = true;

        $pdf->sRecordName = "";
        $pdf->sLastName = $per_LastName;
        $bNoRecordName = true;

        // Find the Head of Household
        $sSQL = "SELECT * FROM $sGroupTable LEFT JOIN family_fam ON per_fam_ID = fam_ID 
            WHERE per_fam_ID = " . $iFamilyID . " 
            AND per_fmr_ID in ($sDirRoleHeads) $sWhereExt $sClassQualifier $sGroupBy";
        $rsPerson = RunQuery($sSQL);

        if (mysql_num_rows($rsPerson) > 0)
        {
            $aHead = mysql_fetch_array($rsPerson);
            array_push($persons, $pdf->pGetPerson($rsCustomFields, $aHead));
            $bNoRecordName = false;
        }

        // Find the Spouse of Household
        $sSQL = "SELECT * FROM $sGroupTable LEFT JOIN family_fam ON per_fam_ID = fam_ID 
            WHERE per_fam_ID = " . $iFamilyID . " 
            AND per_fmr_ID in ($sDirRoleSpouses) $sWhereExt $sClassQualifier $sGroupBy";
        $rsPerson = RunQuery($sSQL);

        if (mysql_num_rows($rsPerson) > 0)
        {
            $aSpouse = mysql_fetch_array($rsPerson);
            array_push($persons, $pdf->pGetPerson($rsCustomFields, $aSpouse));
            $bNoRecordName = false;
        }

        // In case there was no head or spouse, just set record name to family name
        if ($bNoRecordName)
            $pdf->sRecordName = $fam_Name;

        // Find the other members of a family
        $sSQL = "SELECT * FROM $sGroupTable LEFT JOIN family_fam ON per_fam_ID = fam_ID
            WHERE per_fam_ID = " . $iFamilyID . " AND !(per_fmr_ID in ($sDirRoleHeads))
            AND !(per_fmr_ID in ($sDirRoleSpouses))  $sWhereExt $sClassQualifier $sGroupBy ORDER BY per_BirthYear,per_FirstName";
        $rsPerson = RunQuery($sSQL);

        while ($aRow = mysql_fetch_array($rsPerson))
        {
            array_push($persons, $pdf->pGetPerson($rsCustomFields, $aRow));
        }
    }
    else
    {
        array_push($persons, $pdf->pGetPerson($rsCustomFields, $aRow));
    }

    // Count the number of lines in the output string
    $numlines = count($persons) + 1; // address phone line

    if ($numlines > 0)
    {
        // Add section header
        if (strtoupper($sSectionWord) != strtoupper($pdf->sSortBy))
        {
            $pdf->Check_Lines($numlines+2, 0, 0);
            $sSectionWord = strtoupper($pdf->sSortBy);
            $pdf->Add_Header($sSectionWord);
        }
        
        // Add Family/Person Record
        $pdf->Add_Record($persons, $addrPhone, $numlines, $fid, $pid);  // another hack: added +1
    }
}

if($mysqlversion == 3 && $mysqlsubversion >= 22){
    $sSQL = "DROP TABLE IF EXISTS tmp;";
    mysql_query($sSQL,$cnInfoCentral);
}
header('Pragma: public');  // Needed for IE when using a shared SSL certificate
    
if ($iPDFOutputType == 1)
    $pdf->Output("Directory-" . date("Ymd-Gis") . ".pdf", "D");
else
    $pdf->Output();    
?>
