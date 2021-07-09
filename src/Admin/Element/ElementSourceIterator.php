<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
    this source iterator has been modified to fit the gogocarto needs : for the custom form, we are using a hash wich was not recognize
    in the source iterator. I've been modified the getValue method so I can use a hash, using the label as a key. See ElementAdmin.php
    to see ho it is used
*/

namespace App\Admin\Element;

use Closure;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Exporter\Exception\InvalidMethodCallException;
use Exporter\Source\SourceIteratorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;

class ElementSourceIterator implements SourceIteratorInterface
{
        /**
     * @var Query
     */
    protected $query;

    /**
     * @var IterableResult
     */
    protected $iterator;

    /**
     * @var array
     */
    protected $propertyPaths;

    /**
     * @var PropertyAccess
     */
    protected $propertyAccessor;

    /**
     * @var string default DateTime format
     */
    protected $dateTimeFormat;

    /**
     * @param Query  $query          The Doctrine Query
     * @param array  $fields         Fields to export
     * @param string $dateTimeFormat
     */
    public function __construct(Query $query, array $fields, $dateTimeFormat = 'r')
    {
        $this->query = clone $query;
        
        // Remove pagination, accessing private methods of Query object
        $getQueryWithoutPagination = Closure::bind(function() {
            unset($this->query['limit']);
            unset($this->query['skip']);
            return $this;
        }, $this->query, $this->query);
        $this->query = $getQueryWithoutPagination();
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->propertyPaths = [];
        foreach ($fields as $name => $field) {
            if (\is_string($name) && \is_string($field)) {
                $this->propertyPaths[$name] = new PropertyPath($field);
            } else {
                $this->propertyPaths[$field] = new PropertyPath($field);
            }
        }

        $this->dateTimeFormat = $dateTimeFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $element = $this->iterator->current();
        $data = [];
        if ($element->getGeo()) { // Fix strange bug elements without geo object
            $optionsValues = $element->getOptionValues()->toArray();
            foreach ($this->propertyPaths as $name => $propertyPath) {
                if (strpos($propertyPath, 'gogo-option') !== false) {
                    list($a, $optionId, $description) = explode(':@', $propertyPath);
                    $optionValue = array_filter($optionsValues, function($ov) use ($optionId) { 
                        return $ov->getOptionId() == $optionId;
                    });
                    $optionValue = count($optionValue) > 0 ? array_shift($optionValue) : null;
                    $rawValue = $optionValue ? 1 : '';
                    $data[$name] = $this->getValue($rawValue);
                    if ($description) $data[$name . ' - ' . $description] = $optionValue ? $optionValue->getDescription() : '';
                } elseif (strpos($propertyPath, 'gogo-custom') !== false) {
                    $rawValue = $element->getProperty($name);   
                    $data[$name] = $this->getValue($rawValue);
                    // for elements type fields, we export two fields : one with the ids, on with the names.
                    if ($propertyPath == 'gogo-custom-elements' && is_array($rawValue)) {
                        $data[$name . '_ids'] = implode(',', array_keys($rawValue));
                    }                 
                } else {
                    $rawValue = $this->propertyAccessor->getValue($element, $propertyPath);
                    $data[$name] = $this->getValue($rawValue);
                }
            }
        }

        $this->query->getDocumentManager()->getUnitOfWork()->detach($element);
        return $data;
    }

    /**
     * @param $value
     *
     * @return string|null
     */
    protected function getValue($value)
    {
        if (is_object($value)) {
            $value = json_encode($object);
        } elseif (is_array($value)) {
            $value = implode(',', $value);
        } elseif ($value instanceof \Traversable) {
            $value = implode(',', $value->toArray());
        } elseif ($value instanceof \DateTimeInterface) {
            $value = $value->format($this->dateTimeFormat);
        }


        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if ($this->iterator) {
            throw new InvalidMethodCallException('Cannot rewind a Doctrine\ODM\Query');
        }

        $this->iterator = $this->query->iterate();
        $this->iterator->rewind();
    }

    /**
     * @param string $dateTimeFormat
     */
    public function setDateTimeFormat($dateTimeFormat)
    {
        $this->dateTimeFormat = $dateTimeFormat;
    }

    /**
     * @return string
     */
    public function getDateTimeFormat()
    {
        return $this->dateTimeFormat;
    }
}
