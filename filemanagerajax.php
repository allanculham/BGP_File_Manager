<?php

require("configuration.php");
require("include.php");

require_once('./libs/phpseclib/SFTP.php');
require_once("./libs/phpseclib/Crypt/AES.php");


if(!isset($_SESSION['clientid']))
{
	//DON'T KNOW HOW THE REQEUSTOR IS!!
	die();
}

$clientid = $_SESSION['clientid'];

$serverid = '';
$extendedPath = '';
$action = '';

if(!isset($_GET['serverid']) or !isset($_GET['path']) or !isset($_GET['action']))
{
	die();
}


$serverid = $_GET['serverid'];
$extendedPath = $_GET['path'];
$action = $_GET['action'];

$boxDetailsSQL = sprintf("SELECT box.boxid, box.ip, box.login, box.password, box.sshport, srv.path
							FROM %sbox box
							JOIN %sserver srv ON box.boxid = srv.boxid
							JOIN %sgroupMember grpm ON (grpm.groupids LIKE CONCAT(srv.groupid, ';%%')
													  OR grpm.groupids LIKE CONCAT('%%;', srv.groupid, ';%%'))

							WHERE srv.serverid = %d
							AND grpm.clientid = %d;", DBPREFIX, DBPREFIX, DBPREFIX, $serverid, $clientid);
							
$boxDetails = mysql_query($boxDetailsSQL);
$rowsBoxes = mysql_fetch_assoc($boxDetails);

$aes = new Crypt_AES();
$aes->setKeyLength(256);
$aes->setKey(CRYPT_KEY);
		
$sftp= new Net_SFTP($rowsBoxes['ip'], $rowsBoxes['sshport']);

if(!$sftp->login($rowsBoxes['login'], $aes->decrypt($rowsBoxes['password'])))
{
	echo 'Failed to connect';
	die();
}


//ACTION SELECTOR
if($action == 'list')
{
	getlist($rowsBoxes, $extendedPath, $sftp);
}	

if($action == 'fileUpload')
{
	fileUpload($rowsBoxes, $extendedPath, $sftp);

}	

if($action == 'delete')
{
    delete($rowsBoxes, $extendedPath, $sftp);	
}	

//ACTION FUNCTIONS

function delete($rowsBoxes, $extendedPath, $sftp)
{
	$remoteFile = dirname($rowsBoxes['path']).'/'.trim($extendedPath.'/');
	return $sftp->delete($remoteFile, true);
}


function fileUpload($rowsBoxes, $extendedPath, $sftp)
{
	$sourceFile = $_FILES["uploadFile"]["tmp_name"];
	$sourceName = $_FILES["uploadFile"]["name"];
	
	$remoteFile = dirname($rowsBoxes['path']).'/'.trim($extendedPath.'/'.$sourceName, '/');
	
	echo $sftp->put($remoteFile, file_get_contents($sourceFile));
}


function getList($rowsBoxes, $extendedPath, $sftp)
{	

	$path =  dirname($rowsBoxes['path']).$extendedPath.'/';
	
	//echo $path;

	$list = $sftp->rawlist($path);	
	
	if(count($list) > 0)
	{
		foreach($list as $key => $row)
		{
			$names[] = $key;
			$types[] = $row['type'];	
		}

		array_multisort($types, SORT_DESC, $names, SORT_ASC, $list);
	}
	
	echo '<div class="well">';
	echo '<div class="pull-left">'.$extendedPath.'</div>';
	
	echo '<form enctype="multipart/form-data" class="form-inline" name="uploadForm" role="form">';
	echo '<input type="hidden" id="path" name="path" value="'.$extendedPath.'"></input>';
	echo '<div class="input-group pull-right">';
	echo '<input type="file" name="uploadFile" id="uploadFile">';
	echo '<span class="input-group-btn" style="margin-right:5px;">';
    echo '<button id="upload" type="button" class="btn btn-success">Upload</button>';
    echo '</span>';
	
	echo '';
	echo '<button id="delete" type="button" class="btn btn-danger">Delete</button></div>';

	echo '</div>';
	echo '</form>';
	
	RenderFileList($list, $extendedPath);
	

}

function RenderFileList($list, $extendedPath)
{
	echo '<table class="table table-condensed table-striped">';
	echo '<thead>';
	echo '<tr>';
	echo '<th width="16px"></th>';
	echo '<th width="16px"></th>';
	echo '<th>Name</th>';
	echo '<th style="text-align:right">Size</th>';
	echo '<th style="text-align:right">Modified</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	
	foreach($list as $key => $row)
	{
		if($key != '.')
		{
			echo '<tr>';	
			echo '<td><div class="checkbox"><input class="fileSelector" type="checkbox" value="'.$extendedPath.'/'.$key.'"></div></td>';
			echo '<td>';
					
			switch ($row['type'])
			{	
				case 2:
					echo '<span class="icon-folder-close"></span>';
					break;
				case 1;
					echo '<span class="icon-file"></span>';
					break;
			}	
			echo '</td>';
			
			if($key == '..')
			{
				
				echo '<td><b><div title="Return to parent folder" style="cursor: pointer;" class="folder" value="'.dirname($extendedPath).'">'.$key.'</div></b></td>';
				
			
			}
			elseif($row['type'] == 2)
			{
				echo '<td><div style="cursor: pointer;" class="folder" value="'.$extendedPath.'/'.$key.'">'.$key.'</div></td>';
			}
			else	
			{
			echo '<td>'.$key.'</td>';
			}
			
			echo '<td style="text-align:right;">'.formatBytes($row['size']).'</td>';
			echo '<td style="text-align:right;">'.gmdate("Y-m-d H:i:s", $row['mtime']).'</td>';
		}
	}
	echo '</tbody>';
	echo '</table>';
}


function formatBytes($size, $precision = 2)
{
	if($size == 0)
	{
		return 0;
	}else
	{
    $base = log($size) / log(1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   
    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}
}
