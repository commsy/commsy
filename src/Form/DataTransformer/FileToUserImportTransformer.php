<?php


namespace App\Form\DataTransformer;

use App\Form\Model\Csv\CsvUserDataset;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class FileToUserImportTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private string $tempUploadDir;

    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;

    public function __construct(ParameterBagInterface $parameterBag, ValidatorInterface $validator)
    {
        $this->tempUploadDir = $parameterBag->get('files_directory') . '/temp/';
        $this->validator = $validator;
    }

    /**
     * Transforms into a file
     *
     * @param mixed $value
     * @return null
     */
    public function transform($value)
    {
        // We are only using this transformer to transform an uploaded file into an array of
        // CsvUserDataset objects
        return null;
    }

    /**
     * Transforms an uploaded file into an ArrayCollection of CsvUserDataset objects
     *
     * @param UploadedFile $uploadedFile
     * @return ArrayCollection
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    public function reverseTransform($uploadedFile): ArrayCollection
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

        $collection = new ArrayCollection();

        try {
            $uploadedFile->move($this->tempUploadDir, $fileName);

            $content = file_get_contents($this->tempUploadDir . $fileName);

            $fileSystem = new Filesystem();
            $fileSystem->remove($this->tempUploadDir . $fileName);

            $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
            $rows = $serializer->decode($content, 'csv');

            foreach ($rows as $row) {
                if (!$this->checkCsvHeader(array_keys($row))) {
                    throw new TransformationFailedException('header invalid');
                }

                $userDataset = new CsvUserDataset();
                $userDataset->setFirstname($row['firstname'] ?? '');
                $userDataset->setLastname($row['lastname'] ?? '');
                $userDataset->setEmail($row['email'] ?? '');
                $userDataset->setIdentifier($row['identifier'] ?? '');
                $userDataset->setPassword($row['password'] ?? '');
                $userDataset->setRooms($row['rooms'] ?? '');

                $errors = $this->validator->validate($userDataset);
                if ($errors->count()) {
                    throw new TransformationFailedException('row invalid');
                }

                $collection->add($userDataset);
            }
        } catch (FileException $e) {
        }

        return $collection;
    }

    private function checkCsvHeader(array $header): bool
    {
        return array_diff($header, [ 'firstname', 'lastname', 'email', 'identifier', 'password', 'rooms' ]) === [];
    }
}