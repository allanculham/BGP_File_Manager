<?php

$page = 'serverfiles';
$tab = 2;
$isSummary = TRUE;
###
if (isset($_GET['id']) && is_numeric($_GET['id']))
{
	$serverid = $_GET['id'];
}
else
{
	exit('Error: ServerID error.');
}
###
$return = 'serversummary.php?id='.urlencode($serverid);


require("../configuration.php");
require("./include.php");
require("../includes/func.ssh2.inc.php");
require_once("../libs/phpseclib/Crypt/AES.php");


$title = T_('Server Summary');


if (query_numrows( "SELECT `name` FROM `".DBPREFIX."server` WHERE `serverid` = '".$serverid."'" ) == 0)
{
	exit('Error: ServerID is invalid.');
}

$rows = query_fetch_assoc( "SELECT * FROM `".DBPREFIX."server` WHERE `serverid` = '".$serverid."' LIMIT 1" );
$serverIp = query_fetch_assoc( "SELECT `ip` FROM `".DBPREFIX."boxIp` WHERE `ipid` = '".$rows['ipid']."' LIMIT 1" );
$type = query_fetch_assoc( "SELECT `querytype` FROM `".DBPREFIX."game` WHERE `gameid` = '".$rows['gameid']."' LIMIT 1");
$game = query_fetch_assoc( "SELECT * FROM `".DBPREFIX."game` WHERE `gameid` = '".$rows['gameid']."' LIMIT 1" );
$group = query_fetch_assoc( "SELECT `name` FROM `".DBPREFIX."group` WHERE `groupid` = '".$rows['groupid']."' LIMIT 1" );
$logs = mysql_query( "SELECT * FROM `".DBPREFIX."log` WHERE `serverid` = '".$serverid."' ORDER BY `logid` DESC LIMIT 5" );
$box = query_fetch_assoc( "SELECT `ip`, `login`, `password`, `sshport` FROM `".DBPREFIX."box` WHERE `boxid` = '".$rows['boxid']."' LIMIT 1" );



switch(@$_GET['task']){
	case 'serverreinstallgamefiles':{
		$error = '';
		###
		if (empty($serverid))
		{
			$error .= T_('No ServerID specified for server validation !');
		}
		else
		{
			if (!is_numeric($serverid))
			{
				$error .= T_('Invalid ServerID. ');
			}
			else if (query_numrows( "SELECT `name` FROM `".DBPREFIX."server` WHERE `serverid` = '".$serverid."'" ) == 0)
			{
				$error .= T_('Invalid ServerID. ');
			}
		}
		###
		if (!empty($error))
		{
			$_SESSION['msg1'] = T_('Validation Error!');
			$_SESSION['msg2'] = $error;
			$_SESSION['msg-type'] = 'error';
			unset($error);
			header( 'Location: server.php' );
			die();
		}
		###
		###
		$aes = new Crypt_AES();
		$aes->setKeyLength(256);
		$aes->setKey(CRYPT_KEY);
		###
		// Get SSH2 Object OR ERROR String
		$ssh = newNetSSH2($box['ip'], $box['sshport'], $box['login'], $aes->decrypt($box['password']));
		if (!is_object($ssh))
		{
			$_SESSION['msg1'] = T_('Connection Error!');
			$_SESSION['msg2'] = $ssh;
			$_SESSION['msg-type'] = 'error';
			header( "Location: serverfiles.php?id=".urlencode($serverid) );
			die();
		}
		###
		
		$output = $ssh->exec("rm -r -f ".$rows['homedir']."/*");
		if (!empty($output)) //If the output is empty, we consider that there is no errors
		{
			$_SESSION['msg1'] = T_('Error!');
			$_SESSION['msg2'] = T_('Unable to find HOMEDIR path.');
			$_SESSION['msg-type'] = 'error';
			header( "Location: serverfiles.php?id=".urlencode($serverid) );
			die();
		}

		$output = $ssh->exec("cp ".$game['cachedir']."/* ".$rows['homedir']."/");
		if (!empty($output)) //If the output is empty, we consider that there is no errors
		{
			$_SESSION['msg1'] = T_('Error!');
			$_SESSION['msg2'] = T_('Unable to find HOMEDIR path.');
			$_SESSION['msg-type'] = 'error';
			header( "Location: serverfiles.php?id=".urlencode($serverid) );
			die();
		}
		
		$_SESSION['msg1'] = T_('Game Files ReInstalled Successfully!');
		$_SESSION['msg2'] = T_('The game files has been reinstalled.');
		$_SESSION['msg-type'] = 'success';
		
		
		header( "Location: serverfiles.php?id=".urlencode($serverid) );
		die();
		break;
	}

	case 'serverinstallgamefiles':{
		$error = '';
		###
		if (empty($serverid))
		{
			$error .= T_('No ServerID specified for server validation !');
		}
		else
		{
			if (!is_numeric($serverid))
			{
				$error .= T_('Invalid ServerID. ');
			}
			else if (query_numrows( "SELECT `name` FROM `".DBPREFIX."server` WHERE `serverid` = '".$serverid."'" ) == 0)
			{
				$error .= T_('Invalid ServerID. ');
			}
		}
		###
		if (!empty($error))
		{
			$_SESSION['msg1'] = T_('Validation Error!');
			$_SESSION['msg2'] = $error;
			$_SESSION['msg-type'] = 'error';
			unset($error);
			header( 'Location: server.php' );
			die();
		}
		###
		$aes = new Crypt_AES();
		$aes->setKeyLength(256);
		$aes->setKey(CRYPT_KEY);
		###
		// Get SSH2 Object OR ERROR String
		$ssh = newNetSSH2($box['ip'], $box['sshport'], $box['login'], $aes->decrypt($box['password']));
		if (!is_object($ssh))
		{
			$_SESSION['msg1'] = T_('Connection Error!');
			$_SESSION['msg2'] = $ssh;
			$_SESSION['msg-type'] = 'error';
			header( "Location: serverfiles.php?id=".urlencode($serverid) );
			die();
		}
		###
		
		$output = $ssh->exec("cp ".$game['cachedir']."/* ".$rows['homedir']."/");
		if (!empty($output)) //If the output is empty, we consider that there is no errors
		{
			$_SESSION['msg1'] = T_('Error!'.$game['cachedir']);
			$_SESSION['msg2'] = T_('Unable to find HOMEDIR path.');
			$_SESSION['msg-type'] = 'error';
			header( "Location: serverfiles.php?id=".urlencode($serverid) );
			die();
		}
		
		$_SESSION['msg1'] = T_('Game Files Copy Successfully!');
		$_SESSION['msg2'] = T_('The new game files has been added.');
		$_SESSION['msg-type'] = 'success';
		
		
		header( "Location: serverfiles.php?id=".urlencode($serverid) );
		die();
		break;
	}

}

include("./bootstrap/header.php");


/**
 * Notifications
 */
include("./bootstrap/notifications.php");


?>
			<ul class="nav nav-tabs">
				<li><a href="serversummary.php?id=<?php echo $serverid; ?>"><?php echo T_('Summary'); ?></a></li>
				<li><a href="serverprofile.php?id=<?php echo $serverid; ?>"><?php echo T_('Profile'); ?></a></li>
				<li><a href="servermanage.php?id=<?php echo $serverid; ?>"><?php echo T_('Manage'); ?></a></li>
<?php

if ($type['querytype'] != 'none')
{
	echo "\t\t\t\t<li><a href=\"serverlgsl.php?id=".$serverid."\">LGSL</a></li>";
}

?>

<?php

if ($rows['panelstatus'] == 'Started')
{
	echo "\t\t\t\t<li><a href=\"utilitiesrcontool.php?serverid=".$serverid."\">".T_('RCON Tool')."</a></li>";
}

?>

				<li><a href="serverlog.php?id=<?php echo $serverid; ?>"><?php echo T_('Activity Logs'); ?></a></li>
				<li class="active"><a href="serverfiles.php?id=<?php echo $serverid; ?>"><?php echo T_('File Manager'); ?></a></li>
			</ul>
			<div class="row-fluid">
				<div class="span6">
					<div class="well">
						<div style="text-align: center; margin-bottom: 5px;">
							<span class="label label-info"><?php echo T_('Installed Files'); ?></span>
						</div>
						<table class="table table-striped table-bordered table-condensed">
						
						<?php
						
							$server = query_fetch_assoc( "SELECT * FROM `".DBPREFIX."server` WHERE `serverid` = '".$serverid."' LIMIT 1" );
							$box = query_fetch_assoc( "SELECT `ip`, `login`, `password`, `sshport` FROM `".DBPREFIX."box` WHERE `boxid` = '".$server['boxid']."' LIMIT 1" );
							###
							$aes = new Crypt_AES();
							$aes->setKeyLength(256);
							$aes->setKey(CRYPT_KEY);
							###
							// Get SSH2 Object OR ERROR String
							$ssh = newNetSSH2($box['ip'], $box['sshport'], $box['login'], $aes->decrypt($box['password']));
							if (!is_object($ssh))
							{
								$_SESSION['msg1'] = T_('Connection Error!');
								$_SESSION['msg2'] = $ssh;
								$_SESSION['msg-type'] = 'error';
								header( "Location: serverfiles.php?id=".urlencode($serverid) );
								die();
							}
						
							$viewhomedir = null;
							if(isset($_GET['viewhomedir']))
							{
								$viewhomedirto = null;
								$viewhomedir = $_GET['viewhomedir'];
								
								$dir = preg_split('/\//', $viewhomedir, -1, PREG_SPLIT_NO_EMPTY);
								
								
								if(count($dir) > 1)
									for($i = 0; $i < count($dir)-1; $i++)
										$viewhomedirto .= "/".$dir[$i];
								
							if(count($dir) > 1)
								echo "<a style='color:orange;' href='serverfiles.php?id={$serverid}&viewhomedir={$viewhomedirto}'>".T_('Back')."</a>&nbsp; | ";
							echo "<a style='color:orange;' href='serverfiles.php?id={$serverid}'>".T_('Back To Home Dir')."</a><br/>";
							}
						
							$files = preg_split ('/\n/', $ssh->exec("ls ".$server['homedir']."/".$viewhomedir), -1, PREG_SPLIT_NO_EMPTY);
							
							foreach($files as $name)
							{
									echo "<a href='serverfiles.php?id={$serverid}&viewhomedir={$viewhomedir}/{$name}'>".$name."</a>";
									/*<div style='float:right;'>
									<a style='color:red;' href='serverfiles.php?id={$serverid}&delete={$name}'>Delete</a>
									&nbsp;<a style='color:white;' href='serverfiles.php?id={$serverid}&rename={$name}'>Rename</a>
									&nbsp;<a style='color:gray;' href='serverfiles.php?id={$serverid}&deletefile={$name}'>Edit</a></div><br/>";*/
							}	
						?>
						</table>
					</div>
				</div>

				<div class="span6">
					<div class="well">
						<div style="text-align: center; margin-bottom: 5px;">
							<span class="label label-info"><?php echo T_('Installer Files'); ?></span>
						</div>
						<table class="table table-striped table-bordered table-condensed">

						<?php
						
							$server = query_fetch_assoc( "SELECT * FROM `".DBPREFIX."server` WHERE `serverid` = '".$serverid."' LIMIT 1" );
							$box = query_fetch_assoc( "SELECT `ip`, `login`, `password`, `sshport` FROM `".DBPREFIX."box` WHERE `boxid` = '".$server['boxid']."' LIMIT 1" );
							###
							$aes = new Crypt_AES();
							$aes->setKeyLength(256);
							$aes->setKey(CRYPT_KEY);
							###
							// Get SSH2 Object OR ERROR String
							$ssh = newNetSSH2($box['ip'], $box['sshport'], $box['login'], $aes->decrypt($box['password']));
							if (!is_object($ssh))
							{
								$_SESSION['msg1'] = T_('Connection Error!');
								$_SESSION['msg2'] = $ssh;
								$_SESSION['msg-type'] = 'error';
								header( "Location: serverfiles.php?id=".urlencode($serverid) );
								die();
							}
						
						
							$viewcachedir = null;
							if(isset($_GET['viewcachedir']))
							{
								$viewcachedirto = null;
								$viewcachedir = $_GET['viewcachedir'];
								
								$dir = preg_split('/\//', $viewcachedir, -1, PREG_SPLIT_NO_EMPTY);
								
								
								if(count($dir) > 1)
									for($i = 0; $i < count($dir)-1; $i++)
										$viewcachedirto .= "/".$dir[$i];
								
							if(count($dir) > 1)
								echo "<a style='color:orange;' href='serverfiles.php?id={$serverid}&viewcachedir={$viewcachedirto}'>".T_('Back')."</a>&nbsp; | ";
							echo "<a style='color:orange;' href='serverfiles.php?id={$serverid}'>".T_('Back To Home Dir')."</a><br/>";
							}

						
							$files = preg_split ('/\n/', $ssh->exec("ls ".$game['cachedir']."/".$viewcachedir), -1, PREG_SPLIT_NO_EMPTY);
							
							foreach($files as $name)
							{
								echo "<a href='serverfiles.php?id={$serverid}&viewcachedir={$viewcachedir}/{$name}'>".$name."</a><br/><br/>";
							}
						
						?>
						
						</table>
					</div>
				</div>
			</div>

			<div class="row-fluid">
				<div class="span6">
					<div class="well">
						<div style="text-align: center; margin-bottom: 5px;">
							<span class="label label-info"><?php echo T_('Files Control Panel'); ?></span>
						</div>
<?php

if ($rows['status'] == 'Pending')
{
?>
						<div class="alert alert-info">
							<h4 class="alert-heading"><?php echo T_('Server not validated !'); ?></h4>
							<p>
								<?php echo T_('You must validate the server in order to use it.'); ?>
							</p>
						</div>
<?php
}
else if ($rows['status'] == 'Inactive')
{
?>
						<div class="alert alert-block" style="text-align: center;">
							<h4 class="alert-heading"><?php echo T_('The server has been disabled !'); ?></h4>
						</div>
<?php
}
else if ($rows['panelstatus'] == 'Stopped') 
{
?>
						<div style="text-align: center;">
							<a class="btn btn-primary" href="serverfiles.php?task=serverinstallgamefiles&id=<?php echo $serverid; ?>"><?php echo T_('Install Game Files'); ?></a>
							<a class="btn btn-warning" href="serverfiles.php?task=serverreinstallgamefiles&id=<?php echo $serverid; ?>"><?php echo T_('ReInstall Game Files'); ?></a>
						</div>
<?php
}
else if ($rows['panelstatus'] == 'Started') //The server has been validated and is marked as online, the available actions are to restart or to stop it
{
?>
						<div style="text-align: center;">
						<div class="alert alert-block" style="text-align: center;">
							<h4 class="alert-heading"><?php echo T_('The server has been running !'); ?></h4>
						</div>
						</div>
<?php
}

?>
					</div>
				</div>
			</div>

			<script language="javascript" type="text/javascript">
			function deleteServer()
			{
				if (confirm("<?php echo T_('Are you sure you want to delete server:'); ?> <?php echo htmlspecialchars(addslashes($rows['name']), ENT_QUOTES); ?> ?"))
				{
					window.location.href='serverprocess.php?task=serverdelete&serverid=<?php echo $rows['serverid']; ?>';
				}
			}
			</script>
<?php


include("./bootstrap/footer.php");
?>