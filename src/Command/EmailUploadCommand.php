<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use PhpImap\Mailbox;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailUploadCommand extends Command
{
    protected static $defaultName = 'commsy:cron:emailupload';
    protected static $defaultDescription = 'commsy email upload cron';

    private \cs_environment $legacyEnvironment;

    /**
     * @param string $projectDir
     * @param string $uploadEnabled
     * @param string $uploadServer
     * @param string $uploadPort
     * @param string $uploadOptions
     * @param string $uploadAccount
     * @param string $uploadPassword
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private Mailer $mailer,
        private $projectDir,
        private $uploadEnabled,
        private $uploadServer,
        private $uploadPort,
        private $uploadOptions,
        private $uploadAccount,
        private $uploadPassword
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        parent::__construct();
    }

    protected function configure()
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        chdir($this->projectDir.'/legacy/');
        $this->legacyEnvironment->setCacheOff();

        if (!$this->uploadEnabled) {
            $output->writeln('<info>Upload is disabled</info>');

            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $output->writeln('<info>Connecting to mailbox</info>');
        $mailbox = new Mailbox(
            '{'.$this->uploadServer.':'.$this->uploadPort.$this->uploadOptions.'}INBOX',
            $this->uploadAccount,
            $this->uploadPassword,
            $this->projectDir.'/var/temp/'
        );

        // read all messages
        $mailIds = $mailbox->searchMailbox('ALL');

        $output->writeln('<info>Processing '.sizeof($mailIds).' Mails</info>');

        foreach ($mailIds as $mailId) {
            $mail = $mailbox->getMail($mailId);

            $this->emailToCommsy($mail);
            $mailbox->deleteMail($mailId);
        }

        $mailbox->expungeDeletedMails();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    private function emailToCommsy($mail)
    {
        $translation = [];
        $translator = $this->legacyEnvironment->getTranslationObject();

        // split the plain text part
        $bodyLines = preg_split('/\\r\\n|\\r|\\n/', $mail->textPlain);

        // account / secret translations
        $translator->setSelectedLanguage('de');
        $translation['de']['password'] = $translator->getMessage('EMAIL_TO_COMMSY_PASSWORD');
        $translation['de']['account'] = $translator->getMessage('EMAIL_TO_COMMSY_ACCOUNT');
        $translator->setSelectedLanguage('en');
        $translation['en']['password'] = $translator->getMessage('EMAIL_TO_COMMSY_PASSWORD');
        $translation['en']['account'] = $translator->getMessage('EMAIL_TO_COMMSY_ACCOUNT');

        $hasFooter = false;
        $footerStart = 0;
        foreach ($bodyLines as $line => $bodyLine) {
            if ('-- ' == strip_tags($bodyLine)) {
                $hasFooter = true;
                $footerStart = $line;
            }
        }

        $nonMetaLines = [];
        $account = '';
        $secret = '';
        foreach ($bodyLines as $line => $bodyLine) {
            if ($hasFooter && $line == $footerStart) {
                break;
            }

            if (!empty($bodyLine)) {
                $bodyLine = strip_tags($bodyLine);

                $isNonMetaLine = true;

                if (empty($account)) {
                    foreach (['de', 'en'] as $language) {
                        if (stristr($bodyLine, (string) $translation[$language]['account'])) {
                            $accountLine = str_ireplace($translation[$language]['account'].':', '', $bodyLine);
                            $accountLineExp = explode(' ', trim($accountLine));
                            $account = $accountLineExp[0];
                            $isNonMetaLine = false;
                            break;
                        }
                    }
                }

                if (empty($secret)) {
                    foreach (['de', 'en'] as $language) {
                        if (stristr($bodyLine, (string) $translation[$language]['password'])) {
                            $passwordLine = str_ireplace($translation[$language]['password'].':', '', $bodyLine);
                            $passwordLineExp = explode(' ', trim($passwordLine));
                            $secret = $passwordLineExp[0];
                            $isNonMetaLine = false;
                            break;
                        }
                    }
                }

                if ($isNonMetaLine) {
                    $nonMetaLines[] = $bodyLine;
                }
            } else {
                $nonMetaLines[] = $bodyLine;
            }
        }

        $nonMetaBody = implode('<br/>', $nonMetaLines);

        $serverItem = $this->legacyEnvironment->getServerItem();
        $portalIds = $serverItem->getPortalIDArray();

        foreach ($portalIds as $portalId) {
            $this->legacyEnvironment->setCurrentPortalID($portalId);

            $userManager = $this->legacyEnvironment->getUserManager();
            $userManager->setContextArrayLimit($portalId);
            $userManager->setEMailLimit($mail->fromAddress);
            $userManager->select();
            $userList = $userManager->get();

            $matchedUsers = [];
            $user = $userList->getFirst();
            while ($user) {
                if (!empty($account)) {
                    if ($account == $user->getUserID()) {
                        $matchedUsers[] = $user;
                    }
                } else {
                    $matchedUsers[] = $user;
                }

                $user = $userList->getNext();
            }

            foreach ($matchedUsers as $matchedUser) {
                $privateRoomUser = $matchedUser->getRelatedPrivateRoomUserItem();
                $privateRoom = $privateRoomUser->getOwnRoom();

                $translator->setSelectedLanguage($privateRoom->getLanguage());

                if ($privateRoom->getEmailToCommSy()) {
                    $privateSecret = $privateRoom->getEmailToCommSySecret();

                    if ($secret == $privateSecret) {
                        $privateRoomId = $privateRoom->getItemID();

                        $this->legacyEnvironment->setCurrentContextID($privateRoomId);
                        $this->legacyEnvironment->setCurrentUser($privateRoomUser);
                        $this->legacyEnvironment->unsetLinkModifierItemManager();

                        // create new material
                        $materialManager = $this->legacyEnvironment->getMaterialManager();

                        $materialItem = $materialManager->getNewItem();
                        $materialItem->setTitle(trim(str_replace($privateSecret.':', '', $mail->subject)));
                        $materialItem->setDescription($nonMetaBody);

                        // attach files
                        $fileManager = $this->legacyEnvironment->getFileManager();
                        $fileManager->setContextLimit($privateRoomId);

                        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
                        $portalMaxFileSize = $portalItem->getMaxUploadSizeInBytes();

                        $fileIdArray = [];
                        $sizeErrors = [];

                        $attachments = $mail->getAttachments();
                        foreach ($attachments as $attachment) {
                            $file = [
                                'name' => $attachment->name,
                                'tmp_name' => $attachment->filePath,
                                'file_id' => $attachment->name.'_'.date('Y-m-d H:i:s'),
                                'file_size' => filesize($attachment->filePath),
                            ];

                            if (filesize($attachment->filePath) <= $portalMaxFileSize) {
                                $fileItem = $fileManager->getNewItem();
                                $fileItem->setTempKey($file['file_id']);
                                $fileItem->setPostFile($file);
                                $fileItem->save();

                                $fileIdArray[] = $fileItem->getFileID();
                            } else {
                                $sizeErrors[] = [
                                    'name' => $file['name'],
                                    'size' => $file['file_size'],
                                ];
                            }
                        }

                        $materialItem->setFileIDArray($fileIdArray);
                        $materialItem->save();

                        // send e-mail with 'material created in your private room' back to sender
                        $body = $translator->getMessage('EMAIL_TO_COMMSY_RESULT_SUCCESS', $privateRoomUser->getFullName())."\n\n";

                        if (!empty($sizeErrors)) {
                            $filesToLarge = '';

                            foreach ($sizeErrors as $sizeError) {
                                $filesToLarge .= '- '.$sizeError['name'].' ('.round($sizeError['size'] / (1024 * 1024), 2).' MB)'."\n";
                            }

                            $body .= $translator->getMessage('EMAIL_TO_COMMSY_RESULT_FILES_TO_LARGE', $portalMaxFileSize / (1024 * 1024), $filesToLarge)."\n\n";
                        }

                        $body .= $translator->getMessage('EMAIL_TO_COMMSY_RESULT_REGARDS');

                        $recipient = RecipientFactory::createFromRaw($mail->fromAddress);
                        $this->mailer->sendRaw(
                            'Upload2CommSy - erfolgreich',
                            $body,
                            $recipient,
                            $this->legacyEnvironment->getCurrentPortalItem()->getTitle()
                        );
                    } else {
                        // send e-mail with 'password or subject not correct' back to sender
                        $body = $translator->getMessage('EMAIL_TO_COMMSY_RESULT_FAILURE', $privateRoomUser->getFullName(), $translator->getMessage('EMAIL_TO_COMMSY_PASSWORD'));

                        $recipient = RecipientFactory::createFromRaw($mail->fromAddress);
                        $this->mailer->sendRaw(
                            'Upload2CommSy - fehlgeschlagen',
                            $body,
                            $recipient,
                            $this->legacyEnvironment->getCurrentPortalItem()->getTitle()
                        );
                    }
                }
            }
        }
    }
}
