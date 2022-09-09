<?php
namespace wcf\system\event\listener;
use wcf\data\user\User;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Listen to group application actions for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3.moderatedUserGroup
 */
class UzbotUserGroupApplicationActionListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// check module
		if (!MODULE_UZBOT) return;
		
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		if ($className == 'wcf\data\user\group\application\UserGroupApplicationAction') {
			$action = $eventObj->getActionName();
			
			// application submitted
			if ($action == 'create') {
				// get application data
				$returnValues = $eventObj->getReturnValues();
				$application = $returnValues['returnValues'];
				
				// Read all active, valid activity bots, abort if none
				$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'usergroup_apply']);
				if (count($bots)) {
					// data
					$group = $application->getGroup();
					$userProfile = $application->getUserProfile();
					
					// preset placeholders
					$placeholders = [];
					$placeholders['applicant-age'] = $userProfile->getAge();
					$placeholders['applicant-email'] = $userProfile->email;
					$placeholders['applicant-id'] = $userProfile->userID;
					$placeholders['applicant-name'] = $userProfile->username;
					$placeholders['applicant-profile'] = $userProfile->getLink();
					$placeholders['applicant-reason'] = $application->reason;
					$placeholders['count'] = 1;
					$placeholders['count-user'] = 1;
					$placeholders['group-name'] = $group->groupName;
					$placeholders['translate'] = ['group-name'];
					
					foreach ($bots as $bot) {
						$affectedUserIDs = $countToUserID = [];
						$count = 1;
						
						// set affected user
						if (!$bot->changeAffected) {
							$affectedUserIDs[] = $userProfile->userID;
						}
						else {
							// get group leaders
							$sql = "SELECT	leaderID
									FROM	wcf".WCF_N."_user_group_leader
									WHERE	groupID = ?";
							$statement = WCF::getDB()->prepareStatement($sql);
							$statement->execute([$group->groupID]);
							while ($row = $statement->fetchArray()) {
								$affectedUserIDs[] = $row['leaderID'];
							}
						}
						
						// log action
						if ($bot->enableLog) {
							if (!$bot->testMode) {
								UzbotLogEditor::create([
										'bot' => $bot,
										'count' => 1,
										'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
												'total' => 1,
												'userIDs' => implode(', ', $affectedUserIDs)
										])
								]);
							}
							else {
								$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
										'objects' => 1,
										'users' => count($affectedUserIDs),
										'userIDs' => implode(', ', $affectedUserIDs)
								]);
								if (mb_strlen($result) > 64000) $result = mb_substr($result, 0, 64000) . ' ...';
								UzbotLogEditor::create([
										'bot' => $bot,
										'count' => 1,
										'testMode' => 1,
										'additionalData' => serialize(['', '', $result])
								]);
							}
						}
						
						// check for and prepare notification
						$notify = $bot->checkNotify(true, true);
						if ($notify === null) continue;
						
						// send to scheduler
						$data = [
								'bot' => $bot,
								'placeholders' => $placeholders,
								'affectedUserIDs' => $affectedUserIDs,
								'countToUserID' => $countToUserID
						];
						
						$job = new NotifyScheduleBackgroundJob($data);
						BackgroundQueueHandler::getInstance()->performJob($job);
					}
				}
			}
			
			// application changed
			if ($action == 'update') {
				
				$application = $eventObj->getObjects()[0]->getDecoratedObject();
				if ($application->applicant != WCF::getUser()->userID) return;
				
				// Read all active, valid activity bots, abort if none
				$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'usergroup_apply_change']);
				if (count($bots)) {
					$group = $application->getGroup();
					$userProfile = $application->getUserProfile();
					$params = $eventObj->getParameters();
					$reason = $params['data']['reason'];
					
					// preset placeholders
					$placeholders = [];
					$placeholders['applicant-age'] = $userProfile->getAge();
					$placeholders['applicant-email'] = $userProfile->email;
					$placeholders['applicant-id'] = $userProfile->userID;
					$placeholders['applicant-name'] = $userProfile->username;
					$placeholders['applicant-profile'] = $userProfile->getLink();
					$placeholders['applicant-reason'] = $reason;
					$placeholders['count'] = 1;
					$placeholders['count-user'] = 1;
					$placeholders['group-name'] = $group->groupName;
					$placeholders['translate'] = ['group-name'];
					
					foreach ($bots as $bot) {
						$affectedUserIDs = $countToUserID = [];
						$count = 1;
						
						// set affected user
						if (!$bot->changeAffected) {
							$affectedUserIDs[] = $userProfile->userID;
						}
						else {
							// get group leaders
							$sql = "SELECT	leaderID
									FROM	wcf".WCF_N."_user_group_leader
									WHERE	groupID = ?";
							$statement = WCF::getDB()->prepareStatement($sql);
							$statement->execute([$group->groupID]);
							while ($row = $statement->fetchArray()) {
								$affectedUserIDs[] = $row['leaderID'];
							}
						}
						
						// log action
						if ($bot->enableLog) {
							if (!$bot->testMode) {
								UzbotLogEditor::create([
										'bot' => $bot,
										'count' => 1,
										'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
												'total' => 1,
												'userIDs' => implode(', ', $affectedUserIDs)
										])
								]);
							}
							else {
								$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
										'objects' => 1,
										'users' => count($affectedUserIDs),
										'userIDs' => implode(', ', $affectedUserIDs)
								]);
								if (mb_strlen($result) > 64000) $result = mb_substr($result, 0, 64000) . ' ...';
								UzbotLogEditor::create([
										'bot' => $bot,
										'count' => 1,
										'testMode' => 1,
										'additionalData' => serialize(['', '', $result])
								]);
							}
						}
						
						// check for and prepare notification
						$notify = $bot->checkNotify(true, true);
						if ($notify === null) continue;
						
						// send to scheduler
						$data = [
								'bot' => $bot,
								'placeholders' => $placeholders,
								'affectedUserIDs' => $affectedUserIDs,
								'countToUserID' => $countToUserID
						];
						
						$job = new NotifyScheduleBackgroundJob($data);
						BackgroundQueueHandler::getInstance()->performJob($job);
					}
				}
			}
		}
		
		if ($className == 'wcf\action\UserGroupApplicationRevokeAction') {
			
			$application = $eventObj->application;
			if ($application->applicant != WCF::getUser()->userID) return;
			
			// Read all active, valid activity bots, abort if none
			$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'usergroup_apply_revoke']);
			if (count($bots)) {
				$group = $application->getGroup();
				$userProfile = $application->getUserProfile();
				
				// preset placeholders
				$placeholders = [];
				$placeholders['applicant-age'] = $userProfile->getAge();
				$placeholders['applicant-email'] = $userProfile->email;
				$placeholders['applicant-id'] = $userProfile->userID;
				$placeholders['applicant-name'] = $userProfile->username;
				$placeholders['applicant-profile'] = $userProfile->getLink();
				$placeholders['applicant-reason'] = $application->reason;
				$placeholders['count'] = 1;
				$placeholders['count-user'] = 1;
				$placeholders['group-name'] = $group->groupName;
				$placeholders['translate'] = ['group-name'];
				
				foreach ($bots as $bot) {
					$affectedUserIDs = $countToUserID = [];
					$count = 1;
					
					// set affected user
					if (!$bot->changeAffected) {
						$affectedUserIDs[] = $userProfile->userID;
					}
					else {
						// get group leaders
						$sql = "SELECT	leaderID
								FROM	wcf".WCF_N."_user_group_leader
								WHERE	groupID = ?";
						$statement = WCF::getDB()->prepareStatement($sql);
						$statement->execute([$group->groupID]);
						while ($row = $statement->fetchArray()) {
							$affectedUserIDs[] = $row['leaderID'];
						}
					}
					
					// log action
					if ($bot->enableLog) {
						if (!$bot->testMode) {
							UzbotLogEditor::create([
									'bot' => $bot,
									'count' => 1,
									'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
											'total' => 1,
											'userIDs' => implode(', ', $affectedUserIDs)
									])
							]);
						}
						else {
							$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
									'objects' => 1,
									'users' => count($affectedUserIDs),
									'userIDs' => implode(', ', $affectedUserIDs)
							]);
							if (mb_strlen($result) > 64000) $result = mb_substr($result, 0, 64000) . ' ...';
							UzbotLogEditor::create([
									'bot' => $bot,
									'count' => 1,
									'testMode' => 1,
									'additionalData' => serialize(['', '', $result])
							]);
						}
					}
					
					// check for and prepare notification
					$notify = $bot->checkNotify(true, true);
					if ($notify === null) continue;
					
					// send to scheduler
					$data = [
							'bot' => $bot,
							'placeholders' => $placeholders,
							'affectedUserIDs' => $affectedUserIDs,
							'countToUserID' => $countToUserID
					];
					
					$job = new NotifyScheduleBackgroundJob($data);
					BackgroundQueueHandler::getInstance()->performJob($job);
				}
			}
		}
	}
}
