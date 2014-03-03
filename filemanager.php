<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * LICENSE:
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @categories	Games/Entertainment, Systems Administration
 * @package		Bright Game Panel
 * @author		warhawk3407 <warhawk3407@gmail.com> @NOSPAM
 * @copyleft	2013
 * @license		GNU General Public License version 3.0 (GPLv3)
 * @version		(Release 0) DEVELOPER BETA 8
 * @link		http://www.bgpanel.net/
 */



$page = 'filemanager';
$tab = 0;
$isSummary = TRUE;
$return = 'index.php';

$serverid = $_GET['serverid'];

require("configuration.php");
require("include.php");


$title = T_('File Manager');

$rows = query_fetch_assoc( "SELECT * FROM `".DBPREFIX."client` WHERE `clientid` = '".$_SESSION['clientid']."' LIMIT 1" );

include("./bootstrap/header.php");


?>

<script type="text/javascript">

	function setupLinks(path){
	
		if (typeof path === 'undefined') { path = ''; }
	
		
		$('.folder').click(function(){		
			
			path = $(this).attr('value');	
			
			$('#filelist').load('filemanagerajax.php?action=list&serverid=<?php echo $serverid;?>&path='+ encodeURIComponent(path), function(){				
				setupLinks(path);			
			});		
		});
		
		$('#delete').click(function(){	
		
			$('.fileSelector').each(function(){
			if (this.checked) {		
			
			var delPath = $(this).val();
			$.ajax({
				url: "filemanagerajax.php?action=delete&serverid=<?php echo $serverid;?>&path="+ encodeURIComponent(delPath),
				type: "GET",
				}).done(function( data ) {
				
					//SET NOTIFICATION HERE
					
					$('#filelist').load('filemanagerajax.php?action=list&serverid=<?php echo $serverid;?>&path='+ encodeURIComponent(path), function(){				
						setupLinks(path);			
					});	

				});
			}			
			})
			
			$('#filelist').load('filemanagerajax.php?action=list&serverid=<?php echo $serverid;?>&path='+ encodeURIComponent(path), function(){				
				setupLinks();			
			});					
	
		});
		
		$('#upload').click(function(){
						
			var path = $('#path').attr('value');	
			var fd = new FormData();
			fd.append("uploadFile", $('#uploadFile').get(0).files[0]);
			
			$.ajax({
			  url: "filemanagerajax.php?action=fileUpload&serverid=<?php echo $serverid;?>&path="+ encodeURIComponent(path),
			  type: "POST",
			  data: fd,
			  processData: false, 
			  contentType: false
			}).done(function( data ) {
				if ( console && console.log ) {
					//console.log(data);
				}

			}).always(function(){
			
				$('#filelist').load('filemanagerajax.php?action=list&serverid=<?php echo $serverid;?>&path='+ encodeURIComponent(path), function(){				
					setupLinks();			
				});			
				
			});

		});

	
	}
	

$(document).ready(function() {

		$('#filelist').load('filemanagerajax.php?action=list&serverid=<?php echo $serverid;?>&path=', function(){

			setupLinks();
		
		});
		

});

</script>
<div id="filelist"></div>

<?php


include("./bootstrap/footer.php");
?>