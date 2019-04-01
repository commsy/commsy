<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 05.09.18
 * Time: 14:45
 */

namespace App\Form\DataTransformer;


use App\Form\Model\Csv\CsvUserDataset;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Base64ToCsvDatasetTransformer implements DataTransformerInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function transform($collection)
    {
        // transform the collection to a base64 encoded csv content
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);

        return base64_encode($serializer->encode('', 'csv'));
    }

    public function reverseTransform($contentAsBase64String)
    {
        // transform the string to an collection using the csv encoder
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        $rows = $serializer->decode(base64_decode($contentAsBase64String), 'csv');

        $collection = new ArrayCollection();
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

        return $collection;
    }

    private function checkCsvHeader(array $header): bool
    {
        return array_diff($header, [ 'firstname', 'lastname', 'email', 'identifier', 'password', 'rooms' ]) === [];
    }
}