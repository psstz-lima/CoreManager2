<?php
/*
    CoreManager, PHP Front End for ArcEmu, MaNGOS, and TrinityCore
    Copyright (C) 2010  CoreManager Project

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


  // level 1 debug prints total queries,
  //  so we would have to close these, or we can't have debug output
  if($debug)
  {
    if (isset($sql))
      $sql->close();
    if (isset($sqll))
      $sqll->close();
    if (isset($sqlc))
      $sqlc->close();
    if (isset($sqlm))
      $sqlm->close();
    if (isset($sqlw))
      $sqlw->close();

    // level 3 debug lists all global vars, but can't read classes
    // level 4 debug prints all global arrays, but can't print content of classes
    //  so we would have to close these, or we can't have debug output
    if(2 < $debug)
    {
      unset($sql);
      unset($sqll);
      unset($sqlc);
      unset($sqlm);
      unset($sqlw);
    }
  }

  // we start with a lead of 10 spaces,
  //  because last line of header is an opening tag with 8 spaces
  //  so if the file before this follows the indent, we will be at the same place it starts
  //  keep html indent in sync, so debuging from browser source would be easy to read
  $output .= '
          <!-- start of footer.php -->
          </div>
        <div id="body_bottom">';
  $output .= '
          <table class="table_bottom">
            <tr>
              <td class="table_bottom_left"></td>
              <td class="table_bottom_middle">';
  //$lang_footer = lang_footer();
  $output .=
                lang('footer', 'bugs_to_admin').' <a href="mailto:'.$admin_mail.'">'.lang('footer', 'site_admin').'</a><br />';
  //unset($lang_footer);
  unset($admin_mail);
  $output .= sprintf('
                Execute time: %.5f', (microtime(true) - $time_start));
  unset($time_start);

  // if any debug mode is activated, show memory usage
  if($debug)
  {
    $output .= '
                Queries: '.$tot_queries.' on '.$_SERVER['SERVER_SOFTWARE'];
    unset($tot_queries);
    if (function_exists('memory_get_usage'))
      $output .= sprintf('
                <br />Mem. Usage: %.0f/%.0fK Peek: %.0f/%.0fK Global: %.0fK Limit: %s',memory_get_usage()/1024, memory_get_usage(true)/1024,memory_get_peak_usage()/1024,memory_get_peak_usage(true)/1024,sizeof($GLOBALS),ini_get('memory_limit'));
  }

  //---------------------Version Information-------------------------------------

  $output .= '
      <div id="version">'.lang('footer', 'powered').' ';
  if ( $show_version['show'] && $user_lvl >= $show_version['version_lvl'] )
  {
    if ( ( 1 < $show_version['show']) && $user_lvl >= $show_version['svnrev_lvl'] )
    {
      $show_version['svnrev'] = '';
      // if file exists and readable
      if (is_readable('.svn/entries') )
      {
        $file_obj = new SplFileObject('.svn/entries');
        // line 4 is where svn revision is stored
        $file_obj->seek(3);
        $show_version['svnrev'] = $file_obj->current();
        unset($file_obj);
      }
	  $output .= 
        $show_version['version'].lang('footer', 'revision').' <a href="http://trac6.assembla.com/coremanager/changeset/'.$show_version['svnrev'].'">'.$show_version['svnrev'].'</a>';
    }
    else
    {
      $output .= 
        lang('footer', 'version').' '.$show_version['version'].lang('footer', 'revision').' '.$show_version['svnrev'];
    }
  }
  $output .= '</div>';
  //unset($lang_footer);

  // links at footer
  $output .= '
                <p>';
  
  switch ( $core )
  {
    case 1:
    {
      $output .= '
                  <a href="http://www.arcemu.org/" target="_blank"><img src="img/logo-arcemu.png" class="logo_border" alt="arcemu" /></a>';
      break;
    }
    case 2:
    {
      $output .= '
                  <a href="http://getmangos.com/" target="_blank"><img src="img/logo-mangos.png" class="logo_border" alt="mangos" /></a>';
      break;
    }
    case 3:
    {
      $output .= '
                  <a href="http://www.trinitycore.org/" target="_blank"><img src="img/logo-trinity.png" class="logo_border" alt="trinity" /></a>';
      break;
    }
  }
  $output .= '
                  <a href="http://www.php.net/" target="_blank"><img src="img/logo-php.png" class="logo_border" alt="php" /></a>
                  <a href="http://www.mysql.com/" target="_blank"><img src="img/logo-mysql.png" class="logo_border" alt="mysql" /></a>
                  <a href="http://validator.w3.org/check?uri=referer" target="_blank"><img src="img/logo-css.png" class="logo_border" alt="w3" /></a>
                  <br />
                  <a href="http://www.mozilla.com/" target="_blank"><img src="img/logo-firefox.png" class="logo_border" alt="firefox" /></a>
                  <a href="http://www.google.com/chrome?hl=en&brand=CHMI" target="_blank"><img src="img/logo-chrome.png" class="logo_border" alt="firefox" /></a>
                  <a href="http://www.apple.com/safari/" target="_blank"><img src="img/logo-safari.png" class="logo_border" alt="firefox" /></a>
                  <a href="http://www.opera.com/" target="_blank"><img src="img/logo-opera.png" class="logo_border" alt="opera" /></a>
                </p>
              </td>
              <td class="table_bottom_right"></td>
            </tr>
          </table>
          <br />';
  echo $output;
  unset($output);
  // we need to close $output before we start debug mode 3 or higher
  //  we will get double output if we don't
  if(2 < $debug)
  {
    echo '
          <table>
            <tr>
              <td align="left">';
    $arrayObj = new ArrayObject(get_defined_vars());
    for($iterator = $arrayObj->getIterator(); $iterator->valid(); $iterator->next())
    {
      echo '
                <br />'.$iterator->key() . ' => ' . $iterator->current();
    }
    unset($iterator);
    unset($arrayObj);
    // debug mode 3 lists all global vars and their values, but not for arrays
    // debug mode 4 branches all arrays and their content,
    if(3 < $debug)
    {
      echo '
                <pre>';
                  print_r ($GLOBALS);
      echo '
                </pre>';
    }
    echo '
              </td>
            </tr>
          <table>';
  }

?>

        </div>
      </div>
    </center>
  </body>
</html>