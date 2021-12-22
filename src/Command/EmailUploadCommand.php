<?php

namespace App\Command;

use App\Mail\Mailer;
use App\Mail\RecipientFactory;
use App\Services\LegacyEnvironment;
use cs_environment;
use PhpImap\Mailbox;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailUploadCommand extends Command
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var Mailer
     */
    private Mailer $mailer;

    /**
     * @var string
     */
    private string $projectDir;

    /**
     * @var string
     */
    private string $uploadEnabled;

    /**
     * @var string
     */
    private string $uploadServer;

    /**
     * @var string
     */
    private string $uploadPort;

    /**
     * @var string
     */
    private string $uploadOptions;

    /**
     * @var string
     */
    private string $uploadAccount;

    /**
     * @var string
     */
    private string $uploadPassword;

    /**
     * @param LegacyEnvironment $legacyEnvironment
     * @param Mailer $mailer
     * @param $projectDir
     * @param $uploadEnabled
     * @param $uploadServer
     * @param $uploadPort
     * @param $uploadOptions
     * @param $uploadAccount
     * @param $uploadPassword
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        Mailer $mailer,
        $projectDir,
        $uploadEnabled,
        $uploadServer,
        $uploadPort,
        $uploadOptions,
        $uploadAccount,
        $uploadPassword
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->mailer = $mailer;

        $this->projectDir = $projectDir;
        $this->uploadEnabled = $uploadEnabled;
        $this->uploadServer = $uploadServer;
        $this->uploadPort = $uploadPort;
        $this->uploadOptions = $uploadOptions;
        $this->uploadAccount = $uploadAccount;
        $this->uploadPassword = $uploadPassword;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('commsy:cron:emailupload')
            ->setDescription('commsy email upload cron')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        chdir($this->projectDir . '/legacy/');
        $this->legacyEnvironment->setCacheOff();

        if (!$this->uploadEnabled) {
            $output->writeln('<info>Upload is disabled</info>');
            return;
        }

        $output->writeln('<info>Connecting to mailbox</info>');
        $mailbox = new Mailbox(
            '{' . $this->uploadServer . ':' . $this->uploadPort . $this->uploadOptions . '}INBOX',
            $this->uploadAccount,
            $this->uploadPassword,
            $this->projectDir . '/var/temp/'
        );

        // read all messages
        $mailIds = $mailbox->searchMailbox('ALL');

        $output->writeln('<info>Processing ' . sizeof($mailIds) . ' Mails</info>');

        foreach ($mailIds as $mailId) {
            $mail = $mailbox->getMail($mailId);

            $this->emailToCommsy($mail);
            $mailbox->deleteMail($mailId);
        }

        $mailbox->expungeDeletedMails();
    }

    private function emailToCommsy($mail)
    {
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
            if (strip_tags($bodyLine) == '-- ') {
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
                        if (stristr($bodyLine, $translation[$language]['account'])) {
                            $accountLine = str_ireplace($translation[$language]['account'] . ':', '', $bodyLine);
                            $accountLineExp = explode(' ', trim($accountLine));
                            $account = $accountLineExp[0];
                            $isNonMetaLine = false;
                            break;
                        }
                    }
                }

                if (empty($secret)) {
                    foreach (['de', 'en'] as $language) {
                        if (stristr($bodyLine, $translation[$language]['password'])) {
                            $passwordLine = str_ireplace($translation[$language]['password'] . ':', '', $bodyLine);
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

        $nonMetaBody = implode("<br/>", $nonMetaLines);

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
                        $materialItem->setTitle(trim(str_replace($privateSecret . ':', '', $mail->subject)));
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
                                'file_id' => $attachment->name . '_' . date("Y-m-d H:i:s"),
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
                        $body = $translator->getMessage('EMAIL_TO_COMMSY_RESULT_SUCCESS', $privateRoomUser->getFullName()) . "\n\n";

                        if (!empty($sizeErrors)) {
                            $filesToLarge = '';

                            foreach ($sizeErrors as $sizeError) {
                                $filesToLarge .= '- ' . $sizeError['name'].' ('.round($sizeError['size'] / (1024*1024), 2).' MB)'."\n";
                            }

                            $body .= $translator->getMessage('EMAIL_TO_COMMSY_RESULT_FILES_TO_LARGE', $portalMaxFileSize / (1024*1024), $filesToLarge) . "\n\n";
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