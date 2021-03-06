<?php
/**
* Patterns 
*
* Patterns
*
* @package project
* @author Serge J. <jey@tut.by>
* @copyright http://www.atmatic.eu/ (c)
* @version 0.1 (wizard, 15:12:59 [Dec 13, 2011])
*/
//
//
class patterns extends module {
/**
* patterns
*
* Module class constructor
*
* @access private
*/
function patterns() {
  $this->name="patterns";
  $this->title="<#LANG_MODULE_PATTERNS#>";
  $this->module_category="<#LANG_SECTION_OBJECTS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['TAB']=$this->tab;
  if (IsSet($this->script_id)) {
   $out['IS_SET_SCRIPT_ID']=1;
  }
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='patterns' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_patterns') {
   $this->search_patterns($out);
  }
  if ($this->view_mode=='edit_patterns') {
   $this->edit_patterns($out, $this->id);
  }
  if ($this->view_mode=='delete_patterns') {
   $this->delete_patterns($this->id);
   $this->redirect("?");
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* patterns search
*
* @access public
*/
 function search_patterns(&$out) {
  require(DIR_MODULES.$this->name.'/patterns_search.inc.php');
 }
/**
* patterns edit/add
*
* @access public
*/
 function edit_patterns(&$out, $id) {
  require(DIR_MODULES.$this->name.'/patterns_edit.inc.php');
 }

/**
* Title
*
* Description
*
* @access public
*/
 function checkAllPatterns() {
  global $session;

  $current_context=context_getcurrent();

  $patterns=SQLSelect("SELECT * FROM patterns WHERE 1 AND PARENT_ID='".(int)$current_context."' ORDER BY ID");
  $total=count($patterns);
  $res=0;
  for($i=0;$i<$total;$i++) {
   $matched=$this->checkPattern($patterns[$i]['ID']);
   if ($matched) {
    $res=1;
   }
  }

  if (!$res) {
   $patterns=SQLSelect("SELECT patterns.* FROM patterns LEFT JOIN patterns AS p2 ON p2.ID=patterns.PARENT_ID WHERE p2.IS_COMMON_CONTEXT=1 AND patterns.PARENT_ID!=0 ORDER BY patterns.ID");
   $total=count($patterns);
   $res=0;
   for($i=0;$i<$total;$i++) {
    $this->checkPattern($patterns[$i]['ID']);
   }
  }

 }



  function getAvailableActions() {
   $current_context=context_getcurrent();
   $patterns=SQLSelect("SELECT * FROM patterns WHERE 1 AND PARENT_ID='".(int)$current_context."' AND IS_COMMON_CONTEXT!=1 ORDER BY ID");
   $total=count($patterns);
   if (!$total) {
    return 0;
   }
   $res=array();
   for($i=0;$i<$total;$i++) {
    $res[]=$patterns[$i]['TITLE'];
   }

   return $res;

  }

/**
* Title
*
* Description
*
* @access public
*/
 function checkPattern($id) {
  global $session;
  global $pattern_matched;


  $rec=SQLSelectOne("SELECT * FROM patterns WHERE ID='".(int)$id."'");

  if (!$rec['PATTERN']) {
   $pattern=$rec['TITLE'];
  } else {
   $pattern=$rec['PATTERN'];
  }
  $pattern=str_replace("\r", '', $pattern);
  if ($pattern=='') {
   return 0;
  }

  if ($rec['EXECUTED']>0 && $rec['TIME_LIMIT'] && (time()-$rec['EXECUTED'])<=$rec['TIME_LIMIT']) {
   return 0;
  }


  $lines_pattern=explode("\n", $pattern);
  $total_lines=count($lines_pattern);
  if (!$rec['TIME_LIMIT']) {
   $messages=SQLSelect("SELECT MESSAGE FROM shouts ORDER BY ID DESC LIMIT ".(int)$total_lines);
   $messages=array_reverse($messages);
  } else {
   $start_from=time()-$rec['TIME_LIMIT'];
   $messages=SQLSelect("SELECT MESSAGE FROM shouts WHERE ADDED>=('".date('Y-m-d H:i:s', $start_from)."') ORDER BY ADDED");
  }




  $total=count($messages);
  if (!$total) {
   return 0;
  }

  $lines=array();
  for($i=0;$i<$total;$i++) {
   $lines[]=$messages[$i]['MESSAGE'];
  }
  $history=implode('@@@@', $lines);
  $check=implode('@@@@', $lines_pattern);


  if (preg_match('/'.$check.'/is', $history, $matches)) {

    if (checkAccess('pattern', $rec['ID'])) {

     $is_common=0;
     if ($rec['PARENT_ID']) {
      $parent_rec=SQLSelectOne("SELECT IS_COMMON_CONTEXT FROM patterns WHERE ID='".$rec['PARENT_ID']."'");
      $is_common=(int)$parent_rec['IS_COMMON_CONTEXT'];
     }

     if ($rec['IS_CONTEXT']) {
      context_activate($rec['ID']);
     } elseif ($rec['MATCHED_CONTEXT_ID']) {
      context_activate($rec['MATCHED_CONTEXT_ID']);
     } elseif (!$is_common) {
      context_activate(0);
     }

     $rec['LOG']=date('Y-m-d H:i:s').' Pattern matched'."\n".$rec['LOG'];
     $rec['EXECUTED']=time();
     SQLUpdate('patterns', $rec);
     global $noPatternMode;
     $noPatternMode=1;
     $pattern_matched=1;
     if ($rec['SCRIPT_ID']) {
      runScript($rec['SCRIPT_ID'], $matches);
     } elseif ($rec['SCRIPT']) {

                  try {
                   $code=$rec['SCRIPT'];
                   $success=eval($code);
                   if ($success===false) {
                    DebMes("Error in pattern code: ".$code);
                   }
                  } catch(Exception $e){
                   DebMes('Error: exception '.get_class($e).', '.$e->getMessage().'.');
                  }

     }
    $noPatternMode=0;
   }

  }


 if ($pattern_matched) {
  return 1;
 } else {
  return 0;
 }


 }

/**
* patterns delete record
*
* @access public
*/
 function delete_patterns($id) {
  $rec=SQLSelectOne("SELECT * FROM patterns WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM patterns WHERE ID='".$rec['ID']."'");
 }

 function buildTree_patterns($res, $parent_id=0, $level=0) {
  $total=count($res);
  $res2=array();
  for($i=0;$i<$total;$i++) {
   if ($res[$i]['PARENT_ID']==$parent_id) {
    $res[$i]['LEVEL']=$level;
    $res[$i]['RESULT']=$this->buildTree_patterns($res, $res[$i]['ID'], ($level+1));
    $res2[]=$res[$i];
   }
  }
  $total2=count($res2);
  if ($total2) {
   return $res2;
  }
 }


/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS patterns');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
patterns - Patterns
*/
  $data = <<<EOD
 patterns: ID int(10) unsigned NOT NULL auto_increment
 patterns: TITLE varchar(255) NOT NULL DEFAULT ''
 patterns: PATTERN text
 patterns: SCRIPT_ID int(10) NOT NULL DEFAULT '0'
 patterns: SCRIPT text
 patterns: LOG text
 patterns: TIME_LIMIT int(10) NOT NULL DEFAULT '0'
 patterns: EXECUTED int(10) NOT NULL DEFAULT '0'
 patterns: IS_CONTEXT int(3) NOT NULL DEFAULT '0'
 patterns: IS_COMMON_CONTEXT int(3) NOT NULL DEFAULT '0'
 patterns: MATCHED_CONTEXT_ID int(10) NOT NULL DEFAULT '0'
 patterns: TIMEOUT int(10) NOT NULL DEFAULT '0'
 patterns: TIMEOUT_CONTEXT_ID int(10) NOT NULL DEFAULT '0'
 patterns: TIMEOUT_SCRIPT text
 patterns: PARENT_ID int(10) NOT NULL DEFAULT '0'
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgRGVjIDEzLCAyMDExIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
?>