<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminSearchBundle\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\AdminSearchBundle\Filter\ModelFilter;
use Sonata\Form\Type\BooleanType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class FilterTypeGuesser implements TypeGuesserInterface
{
    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property, ModelManagerInterface $modelManager)
    {
        if (!$ret = $modelManager->getParentMetadataForProperty($class, $property, $modelManager)) {
            return false;
        }

        $options = [
            'field_type' => null,
            'field_options' => [],
            'options' => [],
        ];

        list($metadata, $propertyName, $parentAssociationMappings) = $ret;

        $options['parent_association_mappings'] = $parentAssociationMappings;

        if ($metadata->hasAssociation($propertyName)) {
            $mapping = $metadata->getAssociationMapping($propertyName);

            switch ($mapping['type']) {
                case ClassMetadata::ONE_TO_ONE:
                case ClassMetadata::ONE_TO_MANY:
                case ClassMetadata::MANY_TO_ONE:
                case ClassMetadata::MANY_TO_MANY:

                    $options['operator_type']    = EqualOperatorType::class;
                    $options['operator_options'] = array();

                    $options['field_type']    = EntityType::class;
                    $options['field_options'] = array(
                        'class' => $mapping['targetEntity']
                    );

                    $options['field_name']   = $mapping['fieldName'];
                    $options['mapping_type'] = $mapping['type'];

                    return new TypeGuess(ModelFilter::class, $options, Guess::HIGH_CONFIDENCE);
            }
        }

        $options['field_name'] = $metadata->fieldMappings[$propertyName]['fieldName'];

        switch ($metadata->getTypeOfField($propertyName)) {
            case 'boolean':
                $options['field_type'] = BooleanType::class;
                $options['field_options'] = [];

                return new TypeGuess('sonata_search_elastica_boolean', $options, Guess::HIGH_CONFIDENCE);
            case 'datetime':
            case 'vardatetime':
            case 'datetimetz':
                return new TypeGuess('sonata_search_elastica_datetime', $options, Guess::HIGH_CONFIDENCE);
            case 'date':
                return new TypeGuess('sonata_search_elastica_date', $options, Guess::HIGH_CONFIDENCE);
            case 'decimal':
            case 'float':
                $options['field_type'] = NumberType::class;

                return new TypeGuess('sonata_search_elastica_number', $options, Guess::MEDIUM_CONFIDENCE);
            case 'integer':
            case 'bigint':
            case 'smallint':
                $options['field_type'] = NumberType::class;

                return new TypeGuess('sonata_search_elastica_number', $options, Guess::MEDIUM_CONFIDENCE);
            case 'string':
            case 'text':
                $options['field_type'] = TextType::class;

                return new TypeGuess('sonata_search_elastica_string', $options, Guess::MEDIUM_CONFIDENCE);
            case 'time':
                return new TypeGuess('sonata_search_elastica_time', $options, Guess::HIGH_CONFIDENCE);
            default:
                return new TypeGuess('sonata_search_elastica_string', $options, Guess::LOW_CONFIDENCE);
        }
    }
}
