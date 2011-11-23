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


require_once(t3lib_extMgm::extPath('mm_bccmsbase').'lib/class.mmlib_extfrontend.php');

//require_once(t3lib_extMgm::extPath('mm_bccmsbase').'lib/class.mmlib_crypt.php');
//require_once(t3lib_extMgm::extPath('mm_bccmsbase').'lib/class.mmlib_folderui.php');
	
class tx_mmdamchangeinfo_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $frontend 		= null;
	var $cObj			= null;
	var $template		= null;
	var $extKey			= 'mm_dam_changeinfo';
	var $debug			= false;
	var $configuration 	= null;
	
	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		$this->frontend 	= t3lib_div::makeInstance('mmlib_extfrontend');
		$this->frontend->setExtensionKey($this->extKey);
		
		$this->cObj 		= $this->frontend->initLocalCObj();
		$this->template 	= $this->frontend->getTemplateContentFromFilename('template.tmpl');
		
		// Doku: http://typo3.org/documentation/document-library/core-documentation/doc_core_tstemplates/current/view/2/5/
		$this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		//debug($this->configuration);
		
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
		/*
		$this->MOD_MENU = Array (
			"function" => Array (
				"1" => $LANG->getLL("function1"),
				"2" => $LANG->getLL("function2"),
			)
		);
		*/
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
			$this->doc 				= t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath 	= $BACK_PATH;
			$this->doc->form		='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode 		= $this->getJSCode();
			$this->doc->postCode 	= $this->getPostCode();

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
		$function = (string) $this->MOD_SETTINGS["function"];
		$function = 1;
		
		switch($function)	{
			case 1:
				$this->generateContentFunction1();
			break;
			case 2:
				$content="<div align=center><strong>Hello World!</strong></div><br />
					The 'Kickstarter' has made this module automatically, it contains a default framework for a backend module but apart from it does nothing useful until you open the script '".substr(t3lib_extMgm::extPath("mm_dam_changeinfo"),strlen(PATH_site))."mod1/index.php' and edit it!
					<HR>
					<br />This is the GET/POST vars sent to the script:<br />".
					"GET:".t3lib_div::view_array($_GET)."<br />".
					"POST:".t3lib_div::view_array($_POST)."<br />".
					"";
				$this->content.=$this->doc->section("Message #1:",$content,0,1);
			break;
			
		}
	}
	
	/**
	 * Generates the output for the BASE-functionality
	 */
	function generateContentFunction1() {
		global $LANG;
		
		$content = '';
		
		$content .= $this->getDateChooser();
		$content .= $this->getGetPostVars();
		
		$today = mktime(1, 0, 0, date("m"), date("d"), date("Y"));
		
		$checkstart = null;
		if(isset($_POST['submit'])) {
			if(preg_match('#^(\d{4})-(\d{2})-(\d{2})#',$_POST['dob'],$found)) {
				if(count($found) == 4) {
					$checkstart = mktime(1, 0, 0, $found[2], $found[3], $found[1]);	
				}
			}
		}
		
		$this->content.=$this->doc->section($LANG->getLL("section1"),$content,0,1);

		$content = '';
		if($checkstart != null) {
			$useroriented = isset($_POST['useroriented']) ? true : false;
			$content .= $this->getChangedItems($checkstart,$useroriented);
		}
		
		$this->content.=$this->doc->section($LANG->getLL("section2"),$content,0,1);
		
		//debug(t3lib_pageSelect::enableFields('tx_dam'));
	}
	
	/**
	 * Returns the JSCode for this page. The JSCode is requested from the
	 * template file.
	 */
	function getJSCode() {
		$markerArray['###DCPATH###'] 		= $this->getDCPath();
		$markerArray['###TEMPLATEPATH###'] 	= $this->getTemplatePath();
		
		$jscode = $this->cObj->getSubpart($this->template,'###JSCODE###');
		
		return $this->cObj->substituteMarkerArray($jscode,$markerArray);
	}
	
	/**
	 * Returns the JS-POSTCODE for this Module
	 */
	function getPostCode() {
		return $this->cObj->getSubpart($this->template,'###POSTCODE###');	
	}
	
	/**
	 * Returns the HTMLCode for the DateChooser
	 */
	function getDateChooser() {
		global $LANG;
		
		$lables		= array('lable_dc','lable_check','lable_useroriented');
		
		// Preset the LABLES
		foreach($lables as $lable) {
			$markerArray['###' . strtoupper($lable) . '###'] = $LANG->getLL($lable);	
		}
		
		$markerArray['###DCPATH###'] 				= $this->getDCPath();
		$markerArray['###CURRENT_YEAR###'] 			= date("Y");
		$markerArray['###CAL_START###'] 			= date("Y") - 10;
		$markerArray['###DOB###'] 					= isset($_POST['dob']) ? $_POST['dob'] : date('Y-m-d');
		$markerArray['###USERORIENTED_CHECKED###'] 	= isset($_POST['useroriented']) ? 'checked' : '';
		
		$datechooser = $this->cObj->getSubpart($this->template,'###DATECHOOSER###');
		
		return $this->cObj->substituteMarkerArray($datechooser,$markerArray);
	}
	
	/**
	 * Returns the Path to the DateChooser files
	 */
	function getDCPath() {
		return '/' . $this->frontend->extRelPath($this->extKey) . 'mod1/res/date-chooser/';	
	}
	
	function getTemplatePath() {
		return '/' . $this->frontend->extRelPath($this->extKey) . 'mod1/res/';	
	}
	
	/**
	 * Returns the GET / POST Vars
	 */
	function getGetPostVars() {
		if($this->debug == false) return '';
		
		$content = "<br />This is the GET/POST vars sent to the script:<br />".
			"GET:".t3lib_div::view_array($_GET)."<br />".
			"POST:".t3lib_div::view_array($_POST)."<br />";
		
		return $content;
	}
	
	/**
	 * Returns all the changed items
	 * 
	 * @param	[timestamp]		$checkstart - Alle files which are jounger than this date will be selected
	 * @param	[boolean]		$useroriented - If true we parse all users and show the filelist useroriented
	 *  
	 * @return [string] HTML-Content
	 */
	function getChangedItems($checkstart,$useroriented) {
		global $LANG;
		$content	= '';
		
		if($useroriented) {
			$allUsers 	= $this->frontend->getAllUsers();
			
			foreach($allUsers as $userRecord) {
				$groups = explode(',',$userRecord['usergroup']);
				
				foreach($userRecord as $fieldname => $fieldvalue) {
					$markerArrayUsers['###FE_USER_' . strtoupper($fieldname) . '###'] = $fieldvalue;
					}
				
				$content .= $this->generateContentForChangedItems($checkstart,$groups,$markerArrayUsers);
			}
		} else {
			$markerArrayUsers['###FE_USER_USERNAME###'] = $LANG->getLL('lable_all_users');
			$content = $this->generateContentForChangedItems($checkstart,null,$markerArrayUsers);
		}
		return $content;
	}
	
	/**
	 * Generates the output for a list of specific user-groups
	 * 
	 * @param	[timestamp]		$checkstart - Alle files which are jounger than this date will be selected
	 * @param	[boolean]		$useroriented - If true we parse all users and show the filelist useroriented
	 * @param	[array]			$markerArrayUsers - All the fields from the userrecord
	 * 
	 * @return [string] HTML-Content
	 */
	function generateContentForChangedItems($checkstart,$groups,$markerArrayUsers) {
		global	$LANG;
		
		$content 		= '';
		$contentLine 	= array();
		$contentMFLine 	= array();
		
		$template 		= $this->cObj->getSubpart($this->template,'###CHANGED###');
		$changeline		= $this->cObj->getSubpart($template,'###CHANGED_LINE###');
		$mailtext		= $this->cObj->getSubpart($template,'###MAILTEXT###');
		$changeMFLine	= $this->cObj->getSubpart($mailtext,'###CHANGED_MAILFORM_LINE###'); // MailToForms
		$content 		= '';
		$lables			= array('lable_user','lable_email','lable_col_file_name','lable_col_file_path',
							'lable_col_tstamp','lable_col_crdate','lable_nof','lable_send_feedback'
							);
		
		
		if(isset($this->configuration['extConfMailTemplate']) && 
			is_file(PATH_site . $this->configuration['extConfMailTemplate'])) {
				$filename = PATH_site . $this->configuration['extConfMailTemplate'];
				$tempmailtext = implode('',file($filename));
				if(strlen($tempmailtext) > 0) {
					$mailtext		= $tempmailtext;
					$changeMFLine	= $this->cObj->getSubpart($mailtext,'###CHANGED_MAILFORM_LINE###');	
				}
			}
			
		// Preset the LABLES
		foreach($lables as $lable) {
			$markerArrayLables['###' . strtoupper($lable) . '###'] = $LANG->getLL($lable);	
		}
		$markerArrayLables['###TEMPLATEPATH###'] = $this->getTemplatePath();	
		
		// Set the configurations from ext_conf_template
		$markerArrayExtConf = array();
		if($this->configuration != null) {
			foreach($this->configuration as $name => $value) {
				$markerArrayExtConf['###EXT_CONF_' . strtoupper(str_replace('extConf','',$name)) . '###'] = $value;
			}
		}

		//debug("-----------------------",1);
		//debug($userRecord['username'],1);
		
		$res = $this->queryDAMByGroups($checkstart,$groups,false);
		$numberOfChanges = 0;
		
		// Loop throug all the records
		while($res && ($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			unset($markerArrayLine);
			//debug('|_______ File: ' . $record['file_name'] . '(' . $record['file_path'] . ')',1);
			
			// convert all the fields to placeholders for the template
			foreach($record as $fieldname => $fieldvalue) {
				$markername = strtoupper($fieldname);
				
				$markerArrayLine['###TX_DAM_' . $markername . '###'] = $fieldvalue;
				
				if(strstr($fieldname,'time') || strstr($fieldname,'tstamp') || strstr($fieldname,'date')) {
					$markerArrayLine['###TX_DAM_' . $markername . '_YMD###'] = strftime('%Y-%m-%d',$fieldvalue);
				}
			}
				
			// Insert the placeholders into the template
			$contentLine[] 		= $this->cObj->substituteMarkerArray($changeline,$markerArrayLine);
			$contentMFLine[] 	= $this->cObj->substituteMarkerArray($changeMFLine,$markerArrayLine);
			$numberOfChanges++;
		}
		$markerArrayUsers['###NUMBEROFCHANGES###'] = $numberOfChanges;
		
		// bring together all the changed lines to one block
		$tempcontent = $this->cObj->substituteSubpart($template,'###CHANGED_LINE###',implode('',$contentLine));
		
		// First set the lines for the MailText then replace the MAILTEXT in the base-template
		$tempcontentMT = $this->cObj->substituteSubpart($mailtext,'###CHANGED_MAILFORM_LINE###',implode('',$contentMFLine));
		$tempcontent = $this->cObj->substituteSubpart($tempcontent,'###MAILTEXT###',$tempcontentMT);
		
		// set the lables
		$tempcontent = $this->cObj->substituteMarkerArray($tempcontent,$markerArrayLables);

		// set settings from extConf
		$tempcontent = $this->cObj->substituteMarkerArray($tempcontent,$markerArrayExtConf);
		
		// if the query was userspecific, add the placeholders for this user
		// if the query is for all users there is only one entry in the markerArrayUsers: ###FE_USER_USERNAME### which ist set to "All users"
		$content = $this->cObj->substituteMarkerArray($tempcontent,$markerArrayUsers);	
		
		if($markerArrayUsers != null) {
			
			// if user has noch mail - delete this placeholder
			if($markerArrayUsers['###FE_USER_EMAIL###'] == '') {
				$content = $this->cObj->substituteSubpart($content,'###EMAIL###','');
				$content = $this->cObj->substituteSubpart($content,'###MAILFORM###','');
			}
		} else {
			// delete the userspcific things from the template
			$content = $this->cObj->substituteSubpart($tempcontent,'###USERSPECIFIC###','');
			$content = $this->cObj->substituteSubpart($content,'###MAILFORM###','');
		}
		
		return $content;
	}
	
	/**
	 * Looks in the DB for files which are changed and member of the specified groups
	 * 
	 * @param	[timestamp]	$checkstart - date when the file ist changed or chreated
	 * @param	[array]		$groups - file rights must fit to these groups
	 * 
	 */
	function queryDAMByGroups($checkstart,$groups,$withcategory = true) {
    	$SQL['limit']				= '';
		$SQL['select'] 				= 'tx_dam.*,tx_dam_mm_cat.uid_foreign,tx_dam_cat.uid as catid,tx_dam_cat.title as cattitle';
		$SQL['local_table']			= 'tx_dam';
		$SQL['mm_table']			= 'tx_dam_mm_cat';
		$SQL['foreign_table']		= 'tx_dam_cat';
		$SQL['group_by']			= 'tx_dam.file_name';
		$SQL['order_by']			= '';


		$WHERE['enable_fields']		= $this->frontend->enableFields('tx_dam',$groups); //t3lib_pageSelect::enableFields('tx_dam');
		$WHERE['enable_fields_cat']	= $this->frontend->enableFields('tx_dam_cat',$groups);
		$WHERE['recordchanged']		= "AND (tx_dam.tstamp >='$checkstart' OR tx_dam.crdate >='$checkstart')";

		if($withcategory == true) {
			$SQL['where'] = implode(' ',$WHERE);	//$frontend->implodeWithoutBlankPiece(' AND ',$WHERE);	
			
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
		}
		else {
			$SQL['select']		= 'tx_dam.*';
			
			unset($SQL['mm_table']);
			unset($SQL['foreign_table']);
			unset($WHERE['enable_fields_cat']);
			
			$SQL['where'] = preg_replace('#^\s*AND#','',implode(' ',$WHERE));
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$SQL['select'],
				$SQL['local_table'],
				$SQL['where'],             
				$SQL['group_by'],
				$SQL['order_by'],
				$SQL['limit']
				);	
		}
			

		if(!$res) {
			debug('----------- SQL Statement ---------------',1);
			debug(mysql_error(),1);
			debug($SQL);
			debug('++++++++++++++++++++++++++++++++++++++++++',1);
			}
			
		return $res;
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