<?
// blorpscript v1.7.12
// last modified 4/12/01
// copyright (c) 2000-2001 justin frankel
// source distributed under the gnu general public license.
// let me know if you find any security holes (I think I got most of them)
// (justin@blorp.com)

  $blorp_version="1.7.12";

  // load configuration
  $my_convert_path=""; // keep people from specifying convert on the command line 
                       // in case .blorpconfig.php forgets to clear it.
  include '.blorpconfig.php';

// begin functions

  function do_comments($name)
  {
    global $path, $img, $text, $addcomment, $REMOTE_ADDR, $comments_logall;
    if ($path=="") $commentdir = ".comments";
    else $commentdir = ".comments/" . $path;
    if (!file_exists($commentdir))
    {
      $oldumask = umask(0); 
      mkdir($commentdir,0777);
      umask($oldumask); 
    }
    echo "<FORM METHOD=\"GET\" ACTION=\"$thisfn\">Comments:<br>";

    $cfn=$commentdir . "/" . $name . ".txt";
    $setperms=file_exists($cfn);
    $commentfp=@fopen($cfn,$addcomment == "" ? "r":"a+");

    if ($commentfp)
    {
      fseek($commentfp,0);
      if (flock($commentfp,1))
      {
        $rhostcnt=0;
        $addcomment=substr($addcomment,0,400);
        $addcomment=str_replace("\\'","'",$addcomment);
        $addcomment=str_replace('\"','"',$addcomment);
        $addcomment=str_replace('&','&amp',$addcomment);
        $addcomment=str_replace('<','&lt;',$addcomment);
        $addcomment=str_replace('>','&gt;',$addcomment);
        $addcomment=str_replace("\\\\","\\",$addcomment);
        while ($line = fgets($commentfp,1024))
        {
          list ($rhost, $datestr, $ctext) = split(' ',trim($line),3);
          if ($REMOTE_ADDR == $rhost) $rhostcnt=$rhostcnt+1;
          else $rhostcnt=0;
          if ($REMOTE_ADDR == $rhost && $ctext == $addcomment) $addcomment="";
          $rhost=split('\.',$rhost,4);
          echo "$ctext (" . $rhost[0] . "." . $rhost[1] . "." . $rhost[2] . ".x $datestr)<BR>";
        }
        if ($rhostcnt < 3 && $addcomment != "")
        {
          $datestr=date("Md/y/H:i");
          if (flock($commentfp,2)) {
            fseek($commentfp,0,SEEK_END);
            fwrite($commentfp,"$REMOTE_ADDR $datestr $addcomment\n");
            $rhost=split('\.',$REMOTE_ADDR,4);
            echo "<B>added comment</B>: $addcomment (" . $rhost[0] . "." . $rhost[1] . "." . 
                 $rhost[2] . ".x $datestr)<BR>";
          } 
          if ($comments_logall)
          {
            $fp=fopen(".comments/all","a+");
            if ($fp)
            {
              if (flock($fp,2)) 
              { 
                fseek($fp,0,SEEK_END);
                fwrite($fp,"$path/$name: $REMOTE_ADDR@$datestr '$addcomment'\n");
              }
              fclose($fp);
            }
          }
        }
      } 
      fclose($commentfp);
      if (!$setperms) chmod($cfn,0664);
    }
    echo "<INPUT TYPE=hidden NAME=\"path\" VALUE=\"" . rawurlencode($path) . "\">" .
         "<INPUT TYPE=hidden NAME=\"img\" VALUE=\"" . rawurlencode($img) . "\">" .
         "<INPUT TYPE=hidden NAME=\"text\" VALUE=\"" . rawurlencode($text) . "\">" .
         "<INPUT CLASS=myForm TYPE=text SIZE=60 NAME=\"addcomment\">".
         "<INPUT CLASS=myForm TYPE=submit VALUE=\"add\">";
    echo "</FORM>";
  }

  function display_current_path($thispath,$linkcurrent)
  {
    global $sitename;
    if ($thispath=="")
    {
      if (!$linkcurrent) echo "$sitename/";
      else echo "<A HREF=\"$thisfn?path=\">$sitename</A>/";
    }
    else
    {
      $pd_arr=explode("/","$sitename/" . $thispath);
      $parent="";
      if ($linkcurrent) $end=0;
      else $end=1;
      for($g=sizeof($pd_arr)-1;$g>=$end;$g--)
      {
        $pd="";
        for($i=1;$i<sizeof($pd_arr)-$g;$i++) $pd.="/".$pd_arr[$i];
        $newpd=rawurlencode(substr($pd,1));
        $parent=$pd_arr[sizeof($pd_arr)-$g-1];

        if ($g != sizeof($pd_arr)-1) echo "/";

        echo "<A HREF=\"$thisfn?path=$newpd\">$parent</A>";
      }
      if (!$linkcurrent) echo "/" . $pd_arr[sizeof($pd_arr)-1] . "/";
      else echo "/";
    }
  } 

// end functions

  error_reporting(E_PARSE);

// Initialize globals
  if (isset($_GET["path"]))
  {
      $path = $_GET["path"];
  }
  if(isset($_GET["width"]))
  {
      $width = $_GET["width"];
  }
  if(isset($_GET["height"]))
  {
      $height = $_GET["height"];
  }
  if(isset($_SERVER["REMOTE_ADDR"]))
  {
    $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
  }
  if(isset($_GET["img"]))
  {
    $img = $_GET["img"];
  }
  if(isset($_GET["text"]))
  {
    $text = $_GET["text"];
  }
  if(isset($_GET["setrescale"]))
  {
    $setrescale = $_GET["setrescale"];
  }
  if(isset($_GET["addcomment"]))
  {
    $addcomment = $_GET["addcomment"];
  }

  
  if (isset($width))
  {
    if ($width=="") setcookie("$resizecookiename");
    else 
    {
      $width .= "x" . $height;
      setcookie("$resizecookiename", $width);
    }
  } 
  else
  {
    if ($resizecookiename) $width=$_COOKIE[$resizecookiename];
    else $width="";
  }

  if (!isset($path)) $path="";
  else $path=rawurldecode($path);

  if (strstr($path,"..") || substr($path,0,1) == "/" || substr($path,0,1) == ".") $path="";

  $wdp2=$path;
  if (strlen($wdp2) < 1) $wdp2=$defaultroottitle;

  if (isset($img)) 
  {
    if (strstr($img = rawurldecode($img),"/")) $img="";
    if (substr($img,0,1) == ".") $img="";
  }
  else $img="";
  if (isset($text)) 
  {
    if (strstr($text = rawurldecode($text),"/")) $text="";
    if (substr($text,0,1) == ".") $text="";
  }
  else $text="";

  echo "<HTML><HEAD>$headstuff" . 
       "<style type=\"text/css\"> .myform { border-top:$formborder solid thin; border-bottom" .
       ":$formborder solid thin; border-right:$formborder solid thin; border-left:$formborder solid" . 
       " thin; color:$formtextcolor; background:" . $formbg . "; $buttonfontconfig border" .
       "-width:1px; } </style>" .
       "<TITLE>$sitename - $wdp2" . "</TITLE></HEAD><BODY $bodybgconfig text=\"" . 
       "$textcolor\" link=\"$linkcolor\" alink=\"$linkcolor\" vlink=\"$linkcolor\">\n";

  echo "<CENTER>$topline</CENTER>";
  echo "<CENTER><TABLE $tableconfig bgcolor=\"$tablebg\">";

  $wasrescale=0;
  if (isset($setrescale) && $setrescale!="")  // rescale selection
  {
    $wasrescale=1;
    echo "<TR><TD>";
    list ($thisw, $thish) =explode("x",$width);

    echo "<FORM METHOD=\"GET\" ACTION=\"$thisfn\">" .
         "<INPUT class=myform TYPE=submit VALUE=\"Set\"> maximum display size to " .
         " <INPUT Class=myform TYPE=NUMBER NAME=width VALUE=\"$thisw\"" .
         " size=4> by <INPUT Class=myform TYPE=NUMBER NAME=height VALUE=\"$thish\" size=4>&nbsp;&nbsp;";
    if ($path!="") echo "<INPUT TYPE=hidden NAME=path VALUE=\"" . 
                         rawurlencode($path) . "\">";
    if ($text!="") echo  "<INPUT TYPE=hidden NAME=text VALUE=\"" . 
                         rawurlencode($text) . "\">";
    if ($img!="") echo "<INPUT TYPE=hidden NAME=img VALUE=\"" . 
                         rawurlencode($img) . "\">";
    echo "</FORM>";

    echo "<FORM METHOD=\"GET\" ACTION=\"$thisfn\">" .
         "<INPUT class=myform TYPE=submit VALUE=\"Unset\">" .
         " <INPUT Class=myform TYPE=hidden NAME=width VALUE=\"\"iii>";
    if ($path!="") echo "<INPUT TYPE=hidden NAME=path VALUE=\"" . 
                         rawurlencode($path) . "\">";
    if ($text!="") echo  "<INPUT TYPE=hidden NAME=text VALUE=\"" . 
                         rawurlencode($text) . "\">";
    if ($img!="") echo "<INPUT TYPE=hidden NAME=img VALUE=\"" . 
                         rawurlencode($img) . "\">";
    echo "</FORM>";
    echo "</TR></TD>";
  }
  else if ($text != "") 
  {
    echo "<TR><TD VALIGN=TOP><CENTER>";

    display_current_path($path,1);
    echo "<BR>";

    if (!eregi("\.(java|asm|inc|c|cpp|h|phps|php-source)$",$text))
      $fnt=substr($text,0,strlen($text)-strlen(strrchr($text,".")));
    else $fnt=$text;
    echo "<BR><FONT size=+2> <B>$fnt</B></font><BR><BR>";
    if ($path!="") $pathstr=str_replace("%2F","/",rawurlencode($path)) . "/";
    else $pathstr="";
    echo "</center><PRE>";
    if ($path == "") $openfile = $text;
    else $openfile = $path . "/" . $text;
    $fp=fopen($openfile,"r");
    if ($fp)
    {
      if (eregi("\.(java|asm|inc|c|cpp|phps|h|php-source)$",$text)) { 
        while ($line = fgets($fp,1024)) 
        {
          $line = str_replace('&','&amp',$line);
          $line = str_replace('<','&lt;',$line);
          $line = str_replace('>','&gt;',$line);
          $line = str_replace("\t",'  ',$line);
          echo $line;
        }
      }
      else
      {
        while ($line = fgets($fp,1024)) 
        {
          echo $line;
        }
      }
      fclose($fp);
      echo "</PRE></TD></TR>";
      if ($comments_enabled)
      {
        echo "<TR><TD><CENTER>";
        do_comments($text);
        echo "</CENTER></TD></TR>";
      }
    } 
    else echo "</PRE></TD></TR>";
  }
  else if ($img != "") 
  {
    echo "<TR><TD VALIGN=TOP><CENTER>";
    if ($path=="") $newwd="";
    else $newwd=rawurlencode($path);

    $openpath = ($path=="") ? "." : $path;
    $dp=opendir("./" . $openpath);
    if (!$dp)
    { 
      die("Error in URL (" . $openpath . "). Click <a href=\"$thisfn\">here</A>.<BR>");
    }
    $t=(int)0;
    while ($fnt = readdir($dp)) {
      $full_fn=$wd."/".$fnt;
      if (!is_dir($full_fn) && eregi("\.(jpg|jpeg|gif|png)$",$fnt)) 
      {
        if(substr($fnt,0,1) != ".") 
        {
          $t++;
          $fnlist[$fnt]=""; 
        }
      }
    }
    if (!isset($fnlist))
    {
      die("Error in URL (no images found) (" . $openpath . "). Click <a href=\"$thisfn\">here</A>.<BR>");
    }
    closedir($dp);
    ksort($fnlist);
    reset($fnlist);

    $lastfn="";
    $n=(int)0;
    $fixedimg=str_replace("\\'","'",$img);
    $fixedimg=str_replace('\"','"',$fixedimg);
    while ($fntmp = key($fnlist))
    {
      next($fnlist);
      $n++;
      if ($fntmp == $fixedimg) { break; }
      $lastfn=$fntmp;
    }
    if (!$fntmp)
    {
      die("Error in URL (image not found) (" . $openpath . "). Click <a href=\"$thisfn\">here</A>.<BR>");
    }
    $nextfn=key($fnlist);

    display_current_path($path,1);
    echo "<BR>";

    $nameposstr="";
    if (strlen($lastfn) > 0)  // print previous img
    {
      $lastfn=rawurlencode($lastfn);
      $nameposstr .= "<A HREF=\"$thisfn?path=$newwd&img=$lastfn\">$previmagestr</A>&nbsp;&nbsp;";
    }

    if ($path == "") $txtfile=".$img" . ".txt";
    else $txtfile="$path/.$img" . ".txt";
    if (!strstr($txtfile,"..") && file_exists($txtfile) && $file=fopen($txtfile,"r"))
    {
      $line = fgets($file,4096);
      $nameposstr .= chop($line);
    }
    else 
    {
      $file=0;
      $nameposstr .= substr($fixedimg,0,strlen($fixedimg)-strlen(strrchr($fixedimg,".")));
    }

    $nameposstr .= " ($n/$t)";

    if (strlen($nextfn)>0) // next image
    {
      $nextfn=rawurlencode($nextfn);
      $nameposstr .= "&nbsp;&nbsp;<A HREF=\"$thisfn?path=$newwd&img=$nextfn\">$nextimagestr</A>";
    }

    echo "$nameposstr<BR>";
    if ($file != 0)
    {
      $line = fgets($file,4096);
      if ($line)
      {
        echo "<FONT SIZE=-1>";
        do
        {
          echo "$line<BR>";
        }
        while ($line = fgets($file,4096));
        echo "</FONT>";
      }
      fclose($file);
    }
    $newpath = str_replace("%2F","/",rawurlencode($path));
    if ($newpath != "") $newpath .= "/";

    $wstr="";
    if ($width != "")
    {
      $tmp=GetImageSize($path . "/" . $fixedimg);
      $tmp2=explode("x",$width);
      if ($tmp2[1]!=0 && $tmp2[1]!="")
      {
        $outw=0;
        if ($tmp[0] >= $tmp2[0])
        {
          $outw=$tmp2[0];
          $outh=$tmp2[0]*$tmp[1]/$tmp[0];
        }
        else $outh=$tmp[1];

        if ($outh > $tmp2[1])
        {
          $outh=$tmp2[1];
          $outw=$tmp2[1]*$tmp[0]/$tmp[1];
        }
        if ($outw != 0) $wstr="WIDTH=$outw HEIGHT=$outh";
      }
      else // rescale by width only
      {
        if ($tmp[0] >= $width)
        {
          $outw=$width;
          $outh=$width*$tmp[1]/$tmp[0];
          $wstr="WIDTH=$outw HEIGHT=$outh";
        }
      }
    }

    $fixedimg=rawurlencode($fixedimg);
    echo "<IMG $wstr SRC=\"$newpath$fixedimg\"><br>$nameposstr<br>";

    if ($comments_enabled)
    {
      do_comments($img);
    }

    echo "</CENTER>";
    echo $imagebrowsebottom;
    echo "</TD></TR>";
  } else { // browse mode

    // if using convert, find where convert is (you could just manually configure it, too)
    if ($thumbnails == "convert" && $my_convert_path == "")
    {
      // if using win32, you will have to change this to $my_convert_path = "path_to_convert.exe" instead.
      @exec("which convert", $tmp); 
      $my_convert_path=$tmp[0];

      if ($my_convert_path=="") $thumbnails="none";
    }


    echo "<TR><TD COLSPAN=6 NOWRAP VALIGN=TOP><PRE>";

    // read directory
    $openpath = ($path=="") ? "." : $path;
    if (!is_dir($openpath) || !($dp=opendir($openpath)))
    {
      if (!$dp=opendir("."))
        die("Error in URL (" . $path . "). Click <a href=\"$thisfn\">here</A>.<BR>");
      else $path="";
    }

    while ($fn = readdir($dp)) 
    { 
      if(substr($fn,0,1) != ".") {
        $fnlist[$fn]=""; 
      }
    };
    if (!isset($fnlist)) $fnlist[""]="";

    closedir($dp);
    ksort($fnlist);
    reset($fnlist);

    echo "<CENTER>";
    display_current_path($path,0);
    echo "</CENTER>\n";

    // display index text
    if ($path != "") $idxfile=$path . "/.index.txt";
    else $idxfile=".index.txt";

    if (file_exists($idxfile) && $file=fopen($idxfile,"r"))
    {
      $line = fgets($file,4096);
      if ($line)
      {
        echo "<CENTER><B><FONT SIZE=\"+2\">" . chop($line) . "</FONT></B></CENTER>";
        while ($line = fgets($file,4096)) echo chop($line) . "\n";
      }
      fclose($file);
    }
    else
    {
        
        $tt=strrchr($wdp2,"/");
        if (!$tt || strlen($tt)<1) $tt=$wdp2;
        else $tt=substr($tt,1);
        echo "<CENTER><B><FONT SIZE=\"+2\">" . $tt . "</FONT></B></CENTER>\n";
    }


    // directory listing

    reset($fnlist);
    if ($path=="") $newwd="";
    else $newwd=rawurlencode($path . "/");
    $tablestat=2;
    echo "</PRE></TD>";
    while ($fn = key($fnlist))
    {
      next($fnlist);
      if ($path == "") $full_fn="./".$fn;
      else $full_fn=$path."/".$fn;
      if (is_dir($full_fn)) 
      {
         // check for thumbnail
         if ($path == "") $pfn=$fn . "/.dir.jpg";
         else $pfn=$path."/".$fn . "/.dir.jpg";
         $newfn=rawurlencode($fn);
         if ($tablestat==2) { echo "</TR><TR>"; $tablestat=0; }
         echo "<TD COLSPAN=3 WIDTH=50% ALIGN=CENTER>"
             ."<a href=\"$thisfn?path=$newwd$newfn\">";
         if (file_exists($pfn))
         {
           $pfn=str_replace("%2F","/",rawurlencode($pfn));
           echo "<IMG BORDER=0 SRC=\"$pfn\"><BR>$fn/</a>";
         }
         else echo "$fn/</a>";
         echo "</TD>";
         $tablestat=$tablestat+1;
      }
    }
    if ($tablestat==1) { echo "<TD COLSPAN=3 WIDTH=50%>&nbsp;</TD>"; }
    echo "</TR>";

    // image listing

    reset($fnlist);
    $tablestat=0;
    if ($path=="") $newwd="";
    else $newwd=rawurlencode($path);
    if ($path=="") $thumbnaildir = ".thumbnails";
    else $thumbnaildir = ".thumbnails/" . $path;

    if (!file_exists($thumbnaildir))
    {
      $oldumask = umask(0); 
      mkdir($thumbnaildir,0770);
      umask($oldumask); 
    }
    while ($fn = key($fnlist))
    {
      next($fnlist);

      if ($path=="") $full_fn=$fn;
      else $full_fn=$path."/".$fn;

      if (!is_dir($full_fn) && eregi("\.(jpg|jpeg|gif|png)$",$fn)) 
      {
        if ($tablestat == 0) echo "<TR>";
        else if ($tablestat == 3)
        {
          echo "</TR><TR>";
          $tablestat=0;
        }
        $tablestat=$tablestat+1;
        $fnu=rawurlencode($fn);
        $stfn = substr($fn,0,strlen($fn)-strlen(strrchr($fn,".")));
        echo "<TD COLSPAN=2 WIDTH=33% VALIGN=middle ALIGN=center><font size=-1>".
             "<A HREF=\"$thisfn?path=$newwd&img=$fnu\">";
        $thumbnailfile = $thumbnaildir . "/" . $fn;
        if ($path=="") $createfn=$fn;
        else $createfn=$path . "/" . $fn;
        $sizestr="";
        if ($path == "") $txtfile=".$fn" . ".txt";
        else $txtfile="$path/.$fn" . ".txt";
        if (!strstr($txtfile,"..") && file_exists($txtfile) && $file=fopen($txtfile,"r"))
        {
          $line = fgets($file,4096);
          if ($line) $stfn=chop($line);
          fclose($file);
        }
        if ($thumbnails != "none")
        {
          $dothumbnail=1;
          if (!file_exists($thumbnailfile))
          {
            if ($thumbnails == "gd")
            {
              if (eregi("\.gif$",$fn))
              {
                $thumbnailfile=$createfn;
                $tmp=GetImageSize($createfn);
                $newh=$thumbnailheight;
                $neww=$newh/$tmp[1] * $tmp[0];
                if ($neww > $tmp[0])
                {
                  $neww=$tmp[0];
                  $newh=$tmp[1];
                }
                if ($neww > $maxthumbnailwidth)
                {
                  $neww=$maxthumbnailwidth;
                  $newh=$neww/$tmp[0] * $tmp[1];
                }
    
                $sizestr="WIDTH=$neww HEIGHT=$newh";
              }
              else 
              {
                if (eregi("\.(jpg|jpeg)$",$fn))
                  $im = imagecreatefromjpeg($createfn);
                else if (eregi("\.png$",$fn))
                  $im = imagecreatefrompng($createfn);
                if ($im != "")
                {
                  $newh=$thumbnailheight;
                  $neww=$newh/imagesy($im) * imagesx($im);
                  if ($neww > imagesx($im))
                  {
                    $neww=imagesx($im);
                    $newh=imagesy($im);
                  }
                  if ($neww > $maxthumbnailwidth)
                  {
                    $neww=$maxthumbnailwidth;
                    $newh=$neww/imagesx($im) * imagesy($im);
                  }
                  $im2=ImageCreate($neww,$newh);
                  ImageCopyResized($im2,$im,0,0,0,0,$neww,$newh,
                    imagesx($im),imagesy($im));
                  if (eregi("\.(jpg|jpeg)$",$fn))
                    imagejpeg($im2,$thumbnailfile,50);
                  else if (eregi("\.png$",$fn))
                    imagepng($im2,$thumbnailfile);
                  ImageDestroy($im);
                  ImageDestroy($im2);
                }
              }
            }
            else if ($thumbnails == "convert") // imagemagick thumbnails
            {
                $tmp=GetImageSize($createfn);
                $newh=$thumbnailheight;
                $neww=$newh/$tmp[1] * $tmp[0];
                if ($neww > $tmp[0])
                {
                  $neww=$tmp[0];
                  $newh=$tmp[1];
                }
                if ($neww > $maxthumbnailwidth)
                {
                  $neww=$maxthumbnailwidth;
                  $newh=$neww/$tmp[0] * $tmp[1];
                }
                @exec($my_convert_path . ' -geometry ' . round($neww) . "x" . 
                      round($newh) . ' -quality 50 "' . $createfn . '" "' . 
                      $thumbnailfile . '"');
            } 
            else $dothumbnail=0;
          } 
        } // $thumbnails != "none"
        else $dothumbnail=0;

        if ($dothumbnail)
        {
          if ($path=="") $wdtmp="";
          else $wdtmp=str_replace("%2F","/",rawurlencode($path . "/"));
          if ($sizestr=="") 
               echo "<img src=\"%2Ethumbnails/$wdtmp$fnu\" border=0" .
               " align=\"center\" alt=\"\"></a><BR>$stfn";
          else echo "<img src=\"$wdtmp$fnu\" $sizestr border=0" .
               " align=\"center\" alt=\"\"></a><BR>$stfn";
        }
        else echo "$stfn</A>";
          
        echo "</font></TD>";
      }
    }
    if ($tablestat != 0)
    {
      if ($tablestat == 1) echo "<TD COLSPAN=2 WIDTH=33%>&nbsp;</TD>";
      if ($tablestat == 1 || $tablestat == 2) 
                           echo "<TD COLSPAN=2 WIDTH=33%>&nbsp;</TD>";
      echo "</TR><TR>";
    }

    // audio listing

    $tablestat=0;
    reset($fnlist);
    if ($path=="") $newwd="";
    else $newwd=str_replace("%2F","/",rawurlencode($path));
    while ($fn = key($fnlist))
    {
      next($fnlist);
      if ($path=="") $full_fn=$fn;
      else $full_fn=$path."/".$fn;
      $fs=floor(filesize($full_fn)/1000);
      if (!is_dir($full_fn) && eregi("\.(wav|mp2|mp3)$",$fn)) {
        $fnu=rawurlencode($fn);
        if ($tablestat == 0) {
          $tablestat=1;
          echo "<TR><TD COLSPAN=6><pre>";
        }
        echo "AUDIO: <A HREF=\"$newwd/$fnu\">$fn</A> ($fs"."k)\n";
      }
    }

    // movie listing
    reset($fnlist);
    while ($fn = key($fnlist))
    {
      next($fnlist);
      if ($path=="") $full_fn=$fn;
      else $full_fn=$path."/".$fn;
      $fs=floor(filesize($full_fn)/1000);
      if (!is_dir($full_fn) && eregi("\.(avi|mov|mpg|mpeg)$",$fn)) { 
        $fnu=rawurlencode($fn);
        if ($tablestat == 0) {
          $tablestat=1;
          echo "<TR><TD COLSPAN=6><pre>";
        }
        echo "VIDEO: <A HREF=\"$newwd/$fnu\">$fn</A> ($fs"."k)\n";
      }
    }

    // text listing

    reset($fnlist);
    while ($fn = key($fnlist))
    {
      next($fnlist);
      $full_fn=$wd."/".$fn;
      if (!is_dir($full_fn) && eregi("\.TXT$",$fn)) {
        $fnu=rawurlencode($fn);
        if ($tablestat == 0) {
          $tablestat=1;
          echo "<TR><TD COLSPAN=6><PRE>";
        }
        echo "TEXT: <A HREF=\"$thisfn?path=$newwd&text=$fnu\">" . substr($fn,0,strlen($fn)-4) . "</A>\n";
      }
    }

    // code listing
    reset($fnlist);
    if ($path=="") $newwd="";
    else $newwd=rawurlencode($path);
    while ($fn = key($fnlist))
    {
      next($fnlist);
      if ($path=="") $full_fn=$fn;
      else $full_fn=$path."/".$fn;
      if (!is_dir($full_fn) && eregi("\.(java|asm|inc|c|cpp|phps|php-source|h)$",$fn)) { 
        $fnu=rawurlencode($fn);
        if ($tablestat == 0) {
          $tablestat=1;
          echo "<TR><TD COLSPAN=6><PRE>";
        }
        echo "CODE: <A HREF=\"$thisfn?path=$newwd&text=$fnu\">$fn</A>\n";
      }
    }

    // archive listing
    reset($fnlist);
    if ($path=="") $newwd="";
    else $newwd=str_replace("%2F","/",rawurlencode($path));
    while ($fn = key($fnlist))
    {
      next($fnlist);
      if ($path=="") $full_fn=$fn;
      else $full_fn=$path."/".$fn;
      $fs=floor(filesize($full_fn)/1000);
      if (!is_dir($full_fn) && eregi("\.(zip|rar|arj|arc|lzh|lha|tar\.gz|tar|tgz|tar\.bz)$",$fn)) { 
        $fnu=rawurlencode($fn);
        if ($tablestat == 0) {
          $tablestat=1;
          echo "<TR><TD COLSPAN=6><pre>";
        }
        echo "ARCH: <A HREF=\"$newwd/$fnu\">$fn</A> ($fs"."k)\n";
      }
    }





    if ($tablestat == 1) echo "</TD></TR>";
    if ($comments_enabled && !file_exists($path == "" ? 
        ".nodircomments" : ($path . "/.nodircomments")))
    {
      echo "<TR><TD COLSPAN=6><CENTER>";
      do_comments(".index");
      echo "</CENTER></TD></TR>";
    }
  }
  echo "</TABLE></CENTER>";
  echo $bottomlinestart;
  if ($wasrescale==0 && $width!="") echo "Maximum display size: ";
  echo "<A HREF=\"$thisfn?setrescale=";
  if ($wasrescale==0) echo "1"; 
  if ($path!="") echo "&path=" . rawurlencode(str_replace("\\'","'",$path));
  if ($img!="") echo "&img=" . rawurlencode(str_replace("\\'","'",$img));
  if ($text!="") echo "&text=" . rawurlencode(str_replace("\\'","'",$text));
  echo "\">";
  if ($wasrescale!=0) echo "back</a>";
  else if ($width != "") echo "$width</a>";
  else echo "Set</a> maximum display size";
  echo $copyrightstring;
  echo $bottomlineend;
  echo "<script type=\"text/javascript\" src=\"http://mediaplayer.yahoo.com/js\"></script></BODY></HTML>\n";
?>
