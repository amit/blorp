<?
  // rename this file to .blorpconfig.php and put it in the same directory as index.phtml
  
  // specifies how the page should reference itself. i.e. use index.phtml, /, www.poo.com/, or whatever.
  $thisfn="blorpscript.php";

  $sitename="Temporary Location";
  $defaultroottitle="Welcome";
  $resizecookiename="MySiteResizeWidth";

  // colors and formats
  $bodybgconfig="bgcolor=\"#08000F\"";
  $textcolor="#eFeFeF";
  $tablebg="#300040";
  $linkcolor="#00df20";

  // for the rescale form style
  $formborder="#400050";
  $formbg="#500060";
  $formtextcolor="#b000c0";
  $buttonfontconfig="font-family:Courier; font-size:10px;";

  // top line
  $topline="<A href=\"$thisfn\">chakradeo.net</A>";

  // table config
  $tableconfig="BORDER=1 CELLPADDING=4 CELLSPACING=0 BORDERCOLOR=\"#270030\" WIDTH=90%";

  // these are used for the image browser
  $nextimagestr="&gt; &gt;";
  $previmagestr="&lt; &lt;";
  $imagebrowsebottom="<BR>";

  // thumbnail config
  // ($thumbnails can be either "gd" (for GD), "convert" (for imagemagick), or "none", 
  // or "manual" if you like to make your own (and put them in the right places))
  $thumbnails="gd"; 
  $my_convert_path=""; // you probably want to manually configure this for win32.

  $thumbnailheight=100;
  $maxthumbnailwidth=250;

  // bottom line formatting
  $bottomlinestart="<center><font size=-2>";
  $copyrightstring=" - copyright (C) 2000 me<BR>";
  $bottomlineend="</font></center>";

  // comments config
  $comments_enabled=1;  // 0 to disable comment support
  $comments_logall=1;  // 0 to disable all comment logging to .comments/all
?>
