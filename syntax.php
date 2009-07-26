<?php
/**
 * Add Note capability to DokuWiki
 *
 * <note>This is note</note>
 * <note classic>This is note</note>
 * <note important>This is an important note</note>
 * <note warning>This is a big warning</note>
 * <note tip>This is a tip</note>
 *
 * Initially written by:
 * @author     Olivier Cortes <olive@deep-ocean.net>
 * @license    GNU GPL v.2
 *
 * See for more information the bundled README.rdoc file.
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');


class syntax_plugin_note extends DokuWiki_Syntax_Plugin {
    
    var $notes = array(
        'noteimportant' => array('important', 'importante'),
        'notewarning'   => array('warning','bloquante','critique'),
        'notetip'       => array('tip','tuyau','idée'),
        'noteclassic'   => array('','classic','classique')
    );
    
    var $default = 'noteclassic';
    
    function getInfo() {
        return confToHash(dirname(__FILE__).'/INFO.txt');
    }
    
    
    function getType(){ return 'container'; }
    function getPType(){ return 'normal'; }
    function getAllowedTypes() { 
        return array('container','substition','protected','disabled','formatting','paragraphs');
    }
    function getSort(){ return 195; }
    
    // override default accepts() method to allow nesting
    // - ie, to get the plugin accepts its own entry syntax
    function accepts($mode) {
      if ($mode == substr(get_class($this), 7)) return true;
        return parent::accepts($mode);
      }
    
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<note.*?>(?=.*?</note>)',$mode,'plugin_note');
    }
    function postConnect() {
        $this->Lexer->addExitPattern('</note>','plugin_note');
    }
    
    function handle($match, $state, $pos, &$handler) {
    
        switch ($state) {
          
          case DOKU_LEXER_ENTER :
            $note = strtolower(trim(substr($match,5,-1)));
            
            foreach( $this->notes as $class => $names ) {
              if (in_array($note, $names))
                return array($state, $class);
            }
            
            return array($state, $this->default);
          
          case DOKU_LEXER_UNMATCHED :
            return array($state, $match);
          
          default:
            return array($state);
          
        } // close switch
    }
    
    function render($mode, &$renderer, $indata) {
    
        if($mode == 'xhtml') {
        
          list($state, $data) = $indata;
          
          switch ($state) {
            case DOKU_LEXER_ENTER :
              $renderer->doc .= '</p><div class="'.$data.'">';
              break;
            
            case DOKU_LEXER_UNMATCHED :
              $renderer->doc .= $renderer->_xmlEntities($data);
              break;
            
            case DOKU_LEXER_EXIT :
              $renderer->doc .= "\n</div><p>";
              break;
          } // close switch
          return true;
        
        } elseif ($mode == 'odt') {
        
          list($state, $data) = $indata;
          
          switch ($state) {
            case DOKU_LEXER_ENTER :
              $type = substr($data, 4);
              if ($type == "classic") {
                $type = "note"; // the icon for classic notes is named note.png
              }
              $colors = array("note"=>"#eeffff", "warning"=>"#ffdddd", "important"=>"#ffffcc", "tip"=>"#ddffdd");
              $renderer->autostyles["pluginnote"] = '
                  <style:style style:name="pluginnote" style:family="table">
                      <style:table-properties style:width="15cm" table:align="center" style:shadow="#808080 0.18cm 0.18cm"/>
                  </style:style>';
              $renderer->autostyles["pluginnote.A"] = '
                  <style:style style:name="pluginnote.A" style:family="table-column">
                      <style:table-column-properties style:column-width="1.5cm"/>
                  </style:style>';
              $renderer->autostyles["pluginnote.B"] = '
                  <style:style style:name="pluginnote.B" style:family="table-column">
                      <style:table-column-properties style:column-width="13.5cm"/>
                  </style:style>';
              $renderer->autostyles["pluginnote".$type.".A1"] = '
                  <style:style style:name="pluginnote'.$type.'.A1" style:family="table-cell">
                      <style:table-cell-properties style:vertical-align="middle" fo:padding="0.1cm" fo:border-left="0.002cm solid #000000" fo:border-right="none" fo:border-top="0.002cm solid #000000" fo:border-bottom="0.002cm solid #000000" fo:background-color="'.$colors[$type].'"/>
                  </style:style>';
              $renderer->autostyles["pluginnote".$type.".B1"] = '
                  <style:style style:name="pluginnote'.$type.'.B1" style:family="table-cell">
                      <style:table-cell-properties style:vertical-align="middle" fo:padding="0.3cm" fo:border-left="none" fo:border-right="0.002cm solid #000000" fo:border-top="0.002cm solid #000000" fo:border-bottom="0.002cm solid #000000" fo:background-color="'.$colors[$type].'"/>
                  </style:style>';
              // Content
              $renderer->p_close();
              $renderer->doc .= '<table:table table:name="" table:style-name="pluginnote">';
              $renderer->doc .= '<table:table-column table:style-name="pluginnote.A"/>';
              $renderer->doc .= '<table:table-column table:style-name="pluginnote.B"/>';
              $renderer->doc .= '<table:table-row>';
              $renderer->doc .= '<table:table-cell table:style-name="pluginnote'.$type.'.A1" office:value-type="string">';
              // Don't use p_open, as it's not the same style-name
              $renderer->doc .= '<text:p text:style-name="Table_20_Contents">';
              $src = DOKU_PLUGIN."note/images/".$type.".png";
              $renderer->_odtAddImage($src);
              $renderer->doc .= '</text:p>';
              $renderer->doc .= '</table:table-cell>';
              $renderer->doc .= '<table:table-cell table:style-name="pluginnote'.$type.'.B1" office:value-type="string">';
              $renderer->p_open();
              break;
            
            case DOKU_LEXER_UNMATCHED :
              $renderer->cdata($data);
              break;
            
            case DOKU_LEXER_EXIT :
              $renderer->p_close();
              $renderer->doc .= '</table:table-cell>';
              $renderer->doc .= '</table:table-row>';
              $renderer->doc .= '</table:table>';
              $renderer->p_open();
              break;
          } // close switch
          return true;
        }
        
        // unsupported $mode
        return false;
    } // close render function
} // syntax_plugin_note class
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
