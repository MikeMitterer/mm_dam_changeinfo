<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Mike Mitterer <office@bitcon.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module 'DAM Change informer' for the 'mm_dam_changeinfo' extension.
 *
 * @author	Mike Mitterer <office@bitcon.at>
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$LANG->includeLLFile("EXT:mm_dam_changeinfo/mod1/locallang.xml");
require_once (PATH_t3lib."class.t3lib_scbase.php");
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

require_once (PATH_t3lib."class.t3lib_userauth.php");
require_once (PATH_t3lib."class.t3lib_page.php");
require_once (PATH_t3lib."class.t3lib_tstemplate.php");

require_once (PATH_tslib."class.tslib_feuserauth.php");

require_once(t3lib_extMgm::extPath('mm_bccmsbase').'lib/class.mmlib_extfrontend.php');

//require_once(t3lib_extMgm::extPath('mm_bccmsbase').'lib/class.mmlib_crypt.php');
//require_once(t3lib_extMgm::extPath('mm_bccmsbase').'lib/class.mmlib_folderui.php');
	
class tx_mmdamchangeinfo_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $frontend 	= null;
	var $cObj		= null;
	
	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		$this->frontend = new mmlib_extfrontend;
		$this->frontend->setExtensionKey('mm_dam_changeinfo');
		$this->cObj = $this->frontend->initLocalCObj();
		
		/*
		if (t3lib_div::_GP("clear_all_cache"))	{
			$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			"function" => Array (
				"1" => $LANG->getLL("function1"),
				"2" => $LANG->getLL("function2"),
				"3" => $LANG->getLL("function3"),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user["admin"] && !$this->id))	{

				// Draw the header.
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			
			$this->doc->JScode .= '<script src="date-functions.js" type="text/javascript"></script>
<script src="datechooser.js" type="text/javascript"></script>';

			
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$headerSection = $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"])."<br />".$LANG->sL("LLL:EXT:lang/locallang_core.xml:labels.path").": ".t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],50);

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));
			$this->content.=$this->doc->divider(5);


			// Render content:
			$this->moduleContent();


			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section("",$this->doc->makeShortcutIcon("id",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		switch((string)$this->MOD_SETTINGS["function"])	{
			case 1:
				$content="<div align=center><strong>Hello World!</strong></div><br />
					The 'Kickstarter' has made this module automatically, it contains a default framework for a backend module but apart from it does nothing useful until you open the script '".substr(t3lib_extMgm::extPath("mm_dam_changeinfo"),strlen(PATH_site))."mod1/index.php' and edit it!
					<HR>
					<br />This is the GET/POST vars sent to the script:<br />".
					"GET:".t3lib_div::view_array($_GET)."<br />".
					"POST:".t3lib_div::view_array($_POST)."<br />".
					"";
				$this->content.=$this->doc->section("Message #1:",$content,0,1);
			break;
			case 2:
				// $content="<div align=center><strong>Menu item #2...</strong></div>";
				$content="<div align=center><strong>Menu item #2QQQ...</strong></div>";
				$content .= '<select NAME="year">
               <option value="2006">2006</option>
               <option VALUE="2007" selected="selected">2007</option>
         </select><br><br>
    <INPUT name="submit" type="submit"><br><br>
';
				
				$content .= "<br />This is the GET/POST vars sent to the script:<br />".
					"GET:".t3lib_div::view_array($_GET)."<br />".
					"POST:".t3lib_div::view_array($_POST)."<br />";
				
				$this->content.=$this->doc->section("Message #2:",$content,0,1);
				$today = mktime(1, 0, 0, date("m"), date("d"), date("Y"));
				$day = $today;
				
				
				$template = $this->frontend->getTemplateContentFromFilename('template.tmpl');
				$markerArray['###NAME###'] = "Mike";
				debug($this->cObj->substituteMarkerArray($template,$markerArray),1);
				
				
				$allUsers = $frontend->getAllUsers();
				foreach($allUsers as $userRecord) {
					debug("-----------------------",1);
					debug($userRecord['username'],1);
					$groups = explode(',',$userRecord['usergroup']);
					
					//debug($this->getMultipleGroupsWhereClause('tx_dam','fe_group',$groups));
					
					//foreach($groups as $group) {
					//	debug("Group: " . $group,1);
						
//--------------------

			    	$SQL['limit']				= '';
					$SQL['select'] 				= 'tx_dam.*,tx_dam_mm_cat.uid_foreign,tx_dam_cat.uid as catid,tx_dam_cat.title as cattitle';
					$SQL['local_table']			= 'tx_dam';
					$SQL['mm_table']			= 'tx_dam_mm_cat';
					$SQL['foreign_table']		= 'tx_dam_cat';
					$SQL['group_by']			= '';
					$SQL['order_by']			= '';
		
		
					$WHERE['enable_fields']		= $this->enableFields('tx_dam',$groups); //t3lib_pageSelect::enableFields('tx_dam');
					$WHERE['enable_fields_cat']	= $this->enableFields('tx_dam_cat',$groups);
					$WHERE['recordchanged']		= "AND (tx_dam.tstamp >='$day' OR tx_dam.crdate >='$day')";
					$WHERE['statement']			= '';

						
					$SQL['where']				= implode(' ',$WHERE);	//$frontend->implodeWithoutBlankPiece(' AND ',$WHERE);	
					//$SQL['where']				= preg_replace('#^\s*AND#','',$SQL['where']);
					
//----------------	

					//debug($SQL,1);
					//debug($GLOBALS['TCA']['tx_dam']['ctrl']);
					
				 	$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
						$SQL['select'],
						$SQL['local_table'],
						$SQL['mm_table'],
						$SQL['foreign_table'], 
						$SQL['where'],
						$SQL['group_by'],		//	groupBy, 
						$SQL['order_by'],		// 	orderBy,
						$SQL['limit'] 			//	limit
						);
						
					if(!$res) {
						debug('----------- SQL Statement ---------------',1);
						debug(mysql_error(),1);
						debug($SQL);
						debug('++++++++++++++++++++++++++++++++++++++++++',1);
						}
						
					while($res && ($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
						debug('|_______ File: ' . $record['file_name'] . '(' . $record['file_path'] . ')',1);
					}
					
					//}
					
				}
				//debug(t3lib_pageSelect::enableFields('tx_dam'));
				
				break;
			case 3:
				$content="<div align=center><strong>Menu item #3...</strong></div>";
				$this->content.=$this->doc->section("Message #3:",$content,0,1);
			break;
		}
	}
	
        function enableFields($table,$groups,$show_hidden=-1,$ignore_array=array(),$noVersionPreview=FALSE)     {
                 global $TYPO3_CONF_VARS;
 
                 if ($show_hidden==-1 && is_object($GLOBALS['TSFE']))    {       // If show_hidden was not set from outside and if TSFE is an object, set it based on showHiddenPage and showHiddenRecords from TSFE
                         $show_hidden = $table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords;
                 }
                 if ($show_hidden==-1)   $show_hidden=0; // If show_hidden was not changed during the previous evaluation, do it here.
 
                 $ctrl = $GLOBALS['TCA'][$table]['ctrl'];
                 $query='';
                 if (is_array($ctrl))    {
 
                                 // Delete field check:
                         if ($ctrl['delete'])    {
                                 $query.=' AND '.$table.'.'.$ctrl['delete'].'=0';
                         }
 
                                 // Filter out new place-holder records in case we are NOT in a versioning preview (that means we are online!)
                         if ($ctrl['versioningWS'] && !$this->versioningPreview) {
                                 $query.=' AND '.$table.'.t3ver_state!=1';       // Shadow state for new items MUST be ignored!
                         }
 
                                 // Enable fields:
                         if (is_array($ctrl['enablecolumns']))   {
                                 if (!$this->versioningPreview || !$ctrl['versioningWS'] || $noVersionPreview) { // In case of versioning-preview, enableFields are ignored (checked in versionOL())
                                         if ($ctrl['enablecolumns']['disabled'] && !$show_hidden && !$ignore_array['disabled']) {
                                                 $field = $table.'.'.$ctrl['enablecolumns']['disabled'];
                                                 $query.=' AND '.$field.'=0';
                                         }
                                         if ($ctrl['enablecolumns']['starttime'] && !$ignore_array['starttime']) {
                                                 $field = $table.'.'.$ctrl['enablecolumns']['starttime'];
                                                 $query.=' AND ('.$field.'<='.$GLOBALS['SIM_EXEC_TIME'].')';
                                         }
                                         if ($ctrl['enablecolumns']['endtime'] && !$ignore_array['endtime']) {
                                                 $field = $table.'.'.$ctrl['enablecolumns']['endtime'];
                                                 $query.=' AND ('.$field.'=0 OR '.$field.'>'.$GLOBALS['SIM_EXEC_TIME'].')';
                                         }
                                         if ($ctrl['enablecolumns']['fe_group'] && !$ignore_array['fe_group']) {
                                                 $field = $table.'.'.$ctrl['enablecolumns']['fe_group'];
                                                 $query.= $this->getMultipleGroupsWhereClause($field, $table,$groups);
                                         }
                                 }
                         }
                 } else {
                         die ('NO entry in the $TCA-array for the table "'.$table.'". This means that the function enableFields() is called with an invalid table name as argument.');
                 }
 
                 return $query;
         }
	
function getMultipleGroupsWhereClause($field,$table,$groups)   {
                 $orChecks=array();
                 $orChecks[]=$field.'=\'\'';     // If the field is empty, then OK
                 $orChecks[]=$field.'=\'0\'';    // If the field contsains zero, then OK
 
                 // field name must be something like this tx_dam.fe_group (tablename.fieldname)
                 if(strstr($field,'.') == null) $field = $table . '.' . $field;
                 
                 foreach($groups as $value)        {
                         $orChecks[] = $GLOBALS['TYPO3_DB']->listQuery($field, $value, $table);
                 }
 
                 return ' AND ('.implode(' OR ',$orChecks).')';
         }	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mm_dam_changeinfo/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mm_dam_changeinfo/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_mmdamchangeinfo_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>