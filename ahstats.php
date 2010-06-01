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


require_once("header.php");
require_once("libs/get_lib.php");
require_once("libs/item_lib.php");
valid_login($action_permission['view']);

//#############################################################################
// BROWSE AUCTIONS
//#############################################################################
function browse_auctions()
{
  global $output, $characters_db, $world_db, $realm_id,
    $itemperpage, $item_datasite, $server, $user_lvl, $user_id, $sqll, $sqlc;
  //wowhead_tt();

  $red = "\"#DD5047\"";
  $blue = "\"#0097CD\"";
  $sidecolor = array(1 => $blue,2 => $red,3 => $blue,4 => $blue,5 => $red,6 => $red,7 => $blue,8 => $red,10 => $red);
  $hiddencols = array(1,8,9,10);

  //==========================$_GET and SECURE=================================
  $start = (isset($_GET['start'])) ? $sqlc->quote_smart($_GET['start']) : 0;
  if (is_numeric($start)); else $start=0;

  $order_by = (isset($_GET['order_by'])) ? $sqlc->quote_smart($_GET['order_by']) : "time";
  if (!preg_match("/^[_[:lower:]]{1,15}$/", $order_by)) $order_by="time";

  $dir = (isset($_GET['dir'])) ? $sqlc->quote_smart($_GET['dir']) : 1;
  if (!preg_match("/^[01]{1}$/", $dir)) $dir=1;

  $order_dir = ($dir) ? "ASC" : "DESC";
  $dir = ($dir) ? 0 : 1;
  //==========================$_GET and SECURE end=============================

  if( !$user_lvl && !$server[$realm_id]['both_factions'])
  {
    $result = $sqlc->query("SELECT race FROM `characters`
      WHERE account = $user_id AND totaltime = (SELECT MAX(totaltime)
      FROM `characters` WHERE account = $user_id) LIMIT 1");
    if ($sqlc->num_rows($result))
    {
      $order_side = (in_array($sqlc->result($result, 0, 'race'),array(2,5,6,8,10))) ?
      " AND `characters`.`race` IN (2,5,6,8,10) " : " AND `characters`.`race` IN (1,3,4,7,11) ";
    }
    else
      $order_side = "";
  }
  else
    $order_side = "";

  //==========================Browse/Search CHECK==============================
  $search_by = '';
  $search_value = '';
  $search_filter = '';
  $search_class = -1;
  $search_quality = -1;

  if((isset($_GET['search_value']) && isset($_GET['search_by'])) || (isset($_GET['search_class'])) || (isset($_GET['search_quality'])) )
  {
    $search_value = $sqlc->quote_smart($_GET['search_value']);
    $search_by = $sqlc->quote_smart($_GET['search_by']);
    $search_class = $sqlc->quote_smart($_GET['search_class']);
    $search_quality = $sqlc->quote_smart($_GET['search_quality']);

    switch ($search_by)
    {
      case "item_name":
        if(( ($search_class >= 0) || ($search_quality >= 0)) && (!isset($search_value) ))
        {
          if ($search_class >= 0)
            $search_filter = "AND item_template.class = '$search_class'";
          if ($search_quality >= 0)
            $search_filter = "AND item_template.Quality = '$search_quality'";
        }
        else
        {
          $item_prefix = "";
          if ($search_class >= 0)
            $item_prefix .= "AND item_template.class = '$search_class' ";
          if ($search_quality >= 0)
            $item_prefix .= "AND item_template.Quality = '$search_quality' ";

          $result = $sqlc->query("SELECT entry FROM `".$world_db[$realm_id]['name']."`.`item_template` WHERE name LIKE '%$search_value%' $item_prefix");
          $search_filter = "AND auctionhouse.item_template IN(0";
          while ($item = $sqlc->fetch_row($result))
            $search_filter .= ", $item[0]";
            $search_filter .= ")";
        }
        break;
      case "item_id":
        $search_filter = "AND auctionhouse.item_template = '$search_value'";
        break;
      case "seller_name":
        if(( ($search_class >= 0) || ($search_quality >= 0)) && (!isset($search_value) ))
        {
          if ($search_class >= 0)
            $search_filter = "AND item_template.class = '$search_class'";
          if ($search_quality >= 0)
            $search_filter = "AND item_template.Quality = '$search_quality'";
        }
        else
        {
          $item_prefix = "";
          if ($search_class >= 0)
            $item_prefix .= "AND item_template.class = '$search_class' ";
          if ($search_quality >= 0)
            $item_prefix .= "AND item_template.Quality = '$search_quality' ";

          $result = $sqlc->query("SELECT guid FROM `characters` WHERE name LIKE '%$search_value%'");
          $search_filter = $item_prefix;
          $search_filter .= "AND auctionhouse.itemowner IN(0";
          while ($char = $sqlc->fetch_row($result))
            $search_filter .= ", $char[0]";
          $search_filter .= ")";
          $search_filter .= $item_prefix;
        }
        break;
      case "buyer_name":
        if(( ($search_class >= 0) || ($search_quality >= 0)) && (!isset($search_value) ))
        {
          if ($search_class >= 0)
            $search_filter = "AND item_template.class = '$search_class'";
          if ($search_quality >= 0)
            $search_filter = "AND item_template.Quality = '$search_quality'";
        }
        else
        {
          $item_prefix = "";
          if ($search_class >= 0)
            $item_prefix .= "AND item_template.class = '$search_class' ";
          if ($search_quality >= 0)
            $item_prefix .= "AND item_template.Quality = '$search_quality' ";

          $result = $sqlc->query("SELECT guid FROM `characters` WHERE name LIKE '%$search_value%'");
          $search_filter = $item_prefix;
          $search_filter .= "AND auctionhouse.buyguid IN(-1";
          while ($char = $sqlc->fetch_row($result))
            $search_filter .= ", $char[0]";
          $search_filter .= ")";
        }
        break;
      default:
        redirect("ahstats.php?error=1");
    }
    /*$query_1 = $sqlc->query("SELECT count(*)
      FROM `".$characters_db[$realm_id]['name']."`.`characters` , `".$characters_db[$realm_id]['name']."`.`item_instance` ,
      `".$world_db[$realm_id]['name']."`.`item_template` , `".$characters_db[$realm_id]['name']."`.`auctionhouse`
      LEFT JOIN `".$characters_db[$realm_id]['name']."`.`characters` c2 ON `c2`.`guid`=`auctionhouse`.`buyguid`
      WHERE `auctionhouse`.`itemowner`=`characters`.`guid` AND `auctionhouse`.`item_template`=`item_template`.`entry` AND `auctionhouse`.`itemguid`=`item_instance`.`guid`
      $search_filter $order_side");*/

    // this_is_junk: really?
    if ( $core == 1 )
      $query_1 = $sqlc->query("SELECT COUNT(*) FROM auctions");
    else
      $query_1 = $sqlc->query("SELECT COUNT(*) FROM auctionhouse");
  }
  else
  {
    if ( $core == 1 )
      $query_1 = $sqlc->query("SELECT COUNT(*) FROM auctions");
    else
      $query_1 = $sqlc->query("SELECT COUNT(*) FROM auctionhouse");
  }

  /*$result = $sqlc->query("SELECT `characters`.`name` AS `seller`, `auctionhouse`.`item_template` AS `itemid`,
    `item_template`.`name` AS `itemname`, `auctionhouse`.`buyoutprice` AS `buyout`, `auctionhouse`.`time`-unix_timestamp(),
    `c2`.`name` AS `encherisseur`, `auctionhouse`.`lastbid`, `auctionhouse`.`startbid`,
    SUBSTRING_INDEX(SUBSTRING_INDEX(`item_instance`.`data`, ' ',15), ' ',-1) AS qty, `characters`.`race` AS seller_race,
    `c2`.`race` AS buyer_race
    FROM `".$characters_db[$realm_id]['name']."`.`characters` , `".$characters_db[$realm_id]['name']."`.`item_instance` ,
    `".$world_db[$realm_id]['name']."`.`item_template` , `".$characters_db[$realm_id]['name']."`.`auctionhouse`
    LEFT JOIN `".$characters_db[$realm_id]['name']."`.`characters` c2 ON `c2`.`guid`=`auctionhouse`.`buyguid`
    WHERE `auctionhouse`.`itemowner`=`characters`.`guid` AND `auctionhouse`.`item_template`=`item_template`.`entry` AND `auctionhouse`.`itemguid`=`item_instance`.`guid`
    $search_filter
    $order_side ORDER BY `auctionhouse`.`$order_by` $order_dir LIMIT $start, $itemperpage");*/

// this_is_junk: the guid in auction is stored raw, so we have to subtract 4611686018427387904 to get the matching guid stored in playeritems :/
  if ( $core == 1 )
    $result = $sqlc->query("SELECT characters.name as owner_name, owner, playeritems.entry as item_entry, item-4611686018427387904 as item, buyout, `time`-unix_timestamp() as time, bidder, bid FROM auctions $seach_filter
      LEFT JOIN characters ON auctions.owner = characters.guid LEFT JOIN playeritems ON auctions.item-4611686018427387904 = playeritems.guid
      $order_side ORDER BY $order_by $order_dir LIMIT $start, $itemperpage");
  else
    $result = $sqlc->query("SELECT `characters`.`name` AS `seller`, `auctionhouse`.`item_template` AS `itemid`, `item_template`.`name` AS `itemname`, `auctionhouse`.`buyoutprice` AS `buyout`, `auctionhouse`.`time`-unix_timestamp(), `c2`.`name` AS `encherisseur`, `auctionhouse`.`lastbid`, `auctionhouse`.`startbid`, SUBSTRING_INDEX(SUBSTRING_INDEX(`item_instance`.`data`, ' ',15), ' ',-1) AS qty, `characters`.`race` AS seller_race, `c2`.`race` AS buyer_race FROM `".$characters_db[$realm_id]['name']."`.`characters` , `".$characters_db[$realm_id]['name']."`.`item_instance` , `".$world_db[$realm_id]['name']."`.`item_template` , `".$characters_db[$realm_id]['name']."`.`auctionhouse` LEFT JOIN `".$characters_db[$realm_id]['name']."`.`characters` c2 ON `c2`.`guid`=`auctionhouse`.`buyguid` WHERE `auctionhouse`.`itemowner`=`characters`.`guid` AND `auctionhouse`.`item_template`=`item_template`.`entry` AND `auctionhouse`.`itemguid`=`item_instance`.`guid` $search_filter $order_side ORDER BY `auctionhouse`.`$order_by` $order_dir LIMIT $start, $itemperpage");

  $all_record = $sqlc->result($query_1,0);

  //=====================top tage navigaion starts here========================
  $output .="
        <center>
          <table class=\"top_hidden\">
            <tr>
              <td width=\"80%\">
                <form action=\"ahstats.php\" method=\"get\" name=\"form\">
                  <input type=\"hidden\" name=\"error\" value=\"2\" />
                  <table class=\"hidden\">
                    <tr>
                      <td>
                        <input type=\"text\" size=\"24\" name=\"search_value\" value=\"$search_value\" />
                      </td>
                      <td>
                        <select name=\"search_by\">
                          <option".($search_by == 'item_name' ? " selected=\"selected\"" : "")." value=\"item_name\">".lang('auctionhouse', 'item_name')."</option>
                          <option".($search_by == 'item_id' ? " selected=\"selected\"" : "")." value=\"item_id\">".lang('auctionhouse', 'item_id')."</option>
                          <option".($search_by == 'seller_name' ? " selected=\"selected\"" : "")." value=\"seller_name\">".lang('auctionhouse', 'seller_name')."</option>
                          <option".($search_by == 'buyer_name' ? " selected=\"selected\"" : "")." value=\"buyer_name\">".lang('auctionhouse', 'buyer_name')."</option>
                        </select>
                      </td>
                      <td>
                        <select name=\"search_class\">
                          <option".($search_class == -1 ? " selected=\"selected\"" : "")." value=\"-1\">".lang('auctionhouse', 'all')."</option>
                          <option".($search_class == 0 ? " selected=\"selected\"" : "")." value=\"0\">".lang('item', 'consumable')."</option>
                          <option".($search_class == 1 ? " selected=\"selected\"" : "")." value=\"1\">".lang('item', 'bag')."</option>
                          <option".($search_class == 2 ? " selected=\"selected\"" : "")." value=\"2\">".lang('item', 'weapon')."</option>
                          <option".($search_class == 4 ? " selected=\"selected\"" : "")." value=\"4\">".lang('item', 'armor')."</option>
                          <option".($search_class == 5 ? " selected=\"selected\"" : "")." value=\"5\">".lang('item', 'reagent')."</option>
                          <option".($search_class == 7 ? " selected=\"selected\"" : "")." value=\"7\">".lang('item', 'trade_goods')."</option>
                          <option".($search_class == 9 ? " selected=\"selected\"" : "")." value=\"9\">".lang('item', 'recipe')."</option>
                          <option".($search_class == 11 ? " selected=\"selected\"" : "")." value=\"11\">".lang('item', 'quiver')."</option>
                          <option".($search_class == 14 ? " selected=\"selected\"" : "")." value=\"14\">".lang('item', 'permanent')."</option>
                          <option".($search_class == 15 ? " selected=\"selected\"" : "")." value=\"15\">".lang('item', 'misc_short')."</option>
                        </select>
                      </td>
                      <td>
                        <select name=\"search_quality\">
                          <option".($search_quality == -1 ? " selected=\"selected\"" : "")." value=\"-1\">".lang('auctionhouse', 'all')."</option>
                          <option".($search_quality == 0 ? " selected=\"selected\"" : "")." value=\"0\">".lang('item', 'poor')."</option>
                          <option".($search_quality == 1 ? " selected=\"selected\"" : "")." value=\"1\">".lang('item', 'common')."</option>
                          <option".($search_quality == 2 ? " selected=\"selected\"" : "")." value=\"2\">".lang('item', 'uncommon')."</option>
                          <option".($search_quality == 3 ? " selected=\"selected\"" : "")." value=\"3\">".lang('item', 'rare')."</option>
                          <option".($search_quality == 4 ? " selected=\"selected\"" : "")." value=\"4\">".lang('item', 'epic')."</option>
                          <option".($search_quality == 5 ? " selected=\"selected\"" : "")." value=\"5\">".lang('item', 'legendary')."</option>
                          <option".($search_quality == 6 ? " selected=\"selected\"" : "")." value=\"6\">".lang('item', 'artifact')."</option>
                        </select>
                      </td>
                      <td>";
                        makebutton(lang('global', 'search'), "javascript:do_submit()",80);
  $output .= "
                      </td>
                      <td>";
  (($search_by && $search_value) || ($search_class != -1) || ($search_quality != -1)) ? makebutton(lang('global', 'back'), "javascript:window.history.back()",80) : $output .= "";
  $output .= "
                      </td>
                    </tr>
                  </table>
                </form>
              </td>
              <td width=\"25%\" align=\"right\">";
  $output .= generate_pagination("ahstats.php?order_by=$order_by".( (($search_by && $search_value) || ($search_class != -1) || ($search_quality != -1)) ? "&amp;search_by=$search_by&amp;search_value=$search_value&amp;search_quality=$search_quality&amp;search_class=$search_class&amp;error=2" : "" )."&amp;dir=".(($dir) ? 0 : 1), $all_record, $itemperpage, $start);
  $output .= "
              </td>
            </tr>
          </table>
          <table class=\"lined\">
            <tr>
              <th width=\"10%\"><a href=\"ahstats.php?order_by=owner&amp;start=$start".( (($search_by && $search_value) || ($search_class != -1) || ($search_quality != -1)) ? "&amp;search_by=$search_by&amp;search_value=$search_value&amp;search_quality=$search_quality&amp;search_class=$search_class&amp;error=2" : "" )."&amp;dir=$dir\">".($order_by=='owner' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" alt=\"\" /> " : "")."".lang('auctionhouse', 'seller')."</a></th>
              <th width=\"20%\"><a href=\"ahstats.php?order_by=item_entry&amp;start=$start".( (($search_by && $search_value) || ($search_class != -1) || ($search_quality != -1)) ? "&amp;search_by=$search_by&amp;search_value=$search_value&amp;search_quality=$search_quality&amp;search_class=$search_class&amp;error=2" : "" )."&amp;dir=$dir\">".($order_by=='item_entry' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" alt=\"\" /> " : "")."".lang('auctionhouse', 'item')."</a></th>
              <th width=\"15%\"><a href=\"ahstats.php?order_by=buyout&amp;start=$start".( (($search_by && $search_value) || ($search_class != -1) || ($search_quality != -1)) ? "&amp;search_by=$search_by&amp;search_value=$search_value&amp;search_quality=$search_quality&amp;search_class=$search_class&amp;error=2" : "" )."&amp;dir=$dir\">".($order_by=='buyout' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" alt=\"\" /> " : "")."".lang('auctionhouse', 'buyoutprice')."</a></th>
              <th width=\"15%\"><a href=\"ahstats.php?order_by=time&amp;start=$start".( (($search_by && $search_value) || ($search_class != -1) || ($search_quality != -1)) ? "&amp;search_by=$search_by&amp;search_value=$search_value&amp;search_quality=$search_quality&amp;search_class=$search_class&amp;error=2" : "" )."&amp;dir=$dir\">".($order_by=='time' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" alt=\"\" /> " : "")."".lang('auctionhouse', 'timeleft')."</a></th>
              <th width=\"10%\"><a href=\"ahstats.php?order_by=bidder&amp;start=$start".( (($search_by && $search_value) || ($search_class != -1) || ($search_quality != -1)) ? "&amp;search_by=$search_by&amp;search_value=$search_value&amp;search_quality=$search_quality&amp;search_class=$search_class&amp;error=2" : "" )."&amp;dir=$dir\">".($order_by=='bidder' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" alt=\"\" /> " : "")."".lang('auctionhouse', 'buyer')."</a></th>
              <th width=\"15%\"><a href=\"ahstats.php?order_by=bid&amp;start=$start".( (($search_by && $search_value) || ($search_class != -1) || ($search_quality != -1)) ? "&amp;search_by=$search_by&amp;search_value=$search_value&amp;search_quality=$search_quality&amp;search_class=$search_class&amp;error=2" : "" )."&amp;dir=$dir\">".($order_by=='bid' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" alt=\"\" /> " : "")."".lang('auctionhouse', 'lastbid')."</a></th>
            </tr>";
  
  while ($rows = $sqlc->fetch_assoc($result))
  {
    // get item name
    $item_result = $sqlw->result($sqlw->query("SELECT name1 FROM items WHERE entry =".$rows['item_entry']), 0);

    // calculate the buyout value
    $value = $rows['buyout'];
    $g = floor($value/10000);
    $value -= $g*10000;
    $s = floor($value/100);
    $value -= $s*100;
    $c = $value;
    $buyout = $g."<img src=\"./img/gold.gif\" alt=\"\" /> ".$s."<img src=\"./img/silver.gif\" alt=\"\" /> ".$c."<img src=\"./img/copper.gif\" alt=\"\" /> ";
    
    // calculate the remaining time
    $tot_time = $rows['time'];
    $total_days = (int)($tot_time/86400);
    $tot_time = $tot_time - ($tot_days*86400);
    $total_hours = (int)($tot_time/3600);
    $tot_time = $tot_time - ($total_hours*3600);
    $total_min = (int)($tot_time/60);

    // get bidder name
    $bidder_result = $sqlc->result($sqlc->query("SELECT name FROM characters WHERE guid = '".$rows['bidder']."'"), 0);

    // calculate the last bid value
    $value = $rows['bid'];
    $g = floor($value/10000);
    $value -= $g*10000;
    $s = floor($value/100);
    $value -= $s*100;
    $c = $value;
    $bid = $g."<img src=\"./img/gold.gif\" alt=\"\" /> ".$s."<img src=\"./img/silver.gif\" alt=\"\" /> ".$c."<img src=\"./img/copper.gif\" alt=\"\" /> ";


    $output .= "
            <tr>";
    $output .= "
              <td>
                <center>
                  <a href='./char.php?id=".$rows['owner']."'>".$rows['owner_name']."</a>
                </center>
              </td>";
    $output .= "
              <td>
                <center>
                  <a href='".$item_datasite.$rows['item_entry']."' target=\"_blank\" onmouseover=\"toolTip,'item_tooltip')\">".$item_result."</a> (".$rows['item_entry'].")
                </center>
              </td>";
    $output .= "
              <td>
                <center>
                  ".$buyout."
                </center>
              </td>";
    $output .= "
              <td>
                <center>
                  ".($total_days <> 0 ? $total_days." days, " : "").($total_hours <> 0 ? $total_hours." hours, " : "").($total_min<>0 ? $total_min." minutes" : "")."
                </center>
              </td>";
    $output .= "
              <td>
                <center>
                  <a href='./char.php?id=".$rows['bidder']."'>".$bidder_result."</a>
                </center>
              </td>";
    $output .= "
              <td>
                <center>
                  ".($bidder_result <> '' ? $bid : "")."
                </center>
              </td>";
    $output .= "
            </tr>";
                
    /*foreach($rows as $row => $value)
    {
      switch ($row)
      {
        case 4:
          $value = ($value >= 0)? (floor($value / 86400).$lang_auctionhouse['dayshortcut']." ". floor(($value % 86400)/3600).$lang_auctionhouse['hourshortcut']." ".floor((($value % 86400)%3600)/60).$lang_auctionhouse['mnshortcut']) : $lang_auctionhouse['auction_over'];
          break;
        case 5:
          $value = "<b>".((!empty($rows[10])) ? "<font color=".$sidecolor[$rows[10]].">".htmlentities($value)."</font>" : "N/A")."</b>";
          break;
        case 7:
          case 6:
        case 3:
          $g = floor($value/10000);
          $value -= $g*10000;
          $s = floor($value/100);
          $value -= $s*100;
          $c = $value;
          $value = $g."<img src=\"./img/gold.gif\" alt=\"\" /> ".$s."<img src=\"./img/silver.gif\" alt=\"\" /> ".$c."<img src=\"./img/copper.gif\" alt=\"\" /> ";
          break;
        case 2:
          $value = "<a href=\"$item_datasite$rows[1]\" target=\"_blank\" onmouseover=\"toolTip,'item_tooltip')\"><img src=\"".get_item_icon($rows[1], $sqlm, $sqlw)."\" class=\"".get_item_border($rows[1], $sqlw)."\" alt=\"$value\" /><br/>$value".(($rows[8]>1) ? " (x$rows[8])" : "")."</a>";
          break;
        case 0:
          $value = "<b>".((!empty($rows[9])) ? "<font color=".$sidecolor[$rows[9]].">".htmlentities($value)."</font>" : "N/A")."</b>";
          break;
      }
      if (!in_array($row,$hiddencols))
        $output .= "
              <td>
                <center>
                  ".$value."
                </center>
              </td>";
    }*/
    $output .= "
            </tr>";
  }
  $output .= "
            <tr>
              <td  colspan=\"7\" class=\"hidden\" align=\"right\" width=\"25%\">";
  $output .= generate_pagination("ahstats.php?order_by=$order_by".( (($search_by && $search_value) || ($search_class != -1) || ($search_quality != -1)) ? "&amp;search_by=$search_by&amp;search_value=$search_value&amp;search_quality=$search_quality&amp;search_class=$search_class&amp;error=2" : "" )."&amp;dir=".(($dir) ? 0 : 1), $all_record, $itemperpage, $start);
  $output .= "
              </td>
            </tr>
            <tr>
              <td colspan=\"7\" class=\"hidden\" align=\"right\">".lang('auctionhouse', 'total_auctions')." : $all_record
              </td>
            </tr>
          </table>
        </center>";
}


//#############################################################################
// MAIN
//#############################################################################
$err = (isset($_GET['error'])) ? $_GET['error'] : NULL;

$output .= "
      <div class=\"bubble\">
        <div class=\"top\">";

//$lang_auctionhouse = lang_auctionhouse();

switch ($err)
{
  case 1:
    $output .= "
          <h1><font class=\"error\">".lang('global', 'empty_fields')."</font></h1>";
    break;
  case 2:
    $output .= "
          <h1><font class=\"error\">".lang('auctionhouse', 'search_results')."</font></h1>";
    break;
 default:
    $output .= "
          <h1>".lang('auctionhouse', 'auctionhouse')."</h1>";
}

unset($err);

$output .= "
        </div>";

$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

switch ($action)
{
  case "unknown":
    break;
  default:
    browse_auctions();
}

unset($action);
unset($action_permission);
//unset($lang_auctionhouse);

require_once("footer.php");

?>