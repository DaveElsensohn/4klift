<?php

/*
 * This file is part of cef (a 4klift component).
 *
 * Copyright (c) 2017 Deasil Works Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace deasilworks\cef;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use ReflectionClass;
use JMS\Serializer\Annotation\Exclude;

/**
 * Class CEFData.
 *
 * @ExclusionPolicy("none")
 */
class CEFData
{
    /**
     * @Exclude()
     * @var bool
     */
    private $serializeNull = false;

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            $str = $this->toJson();
        } catch (\Exception $e) {
            $str = $e->getTraceAsString();
        }

        return $str;
    }

    /**
     * @return bool
     */
    public function isSerializeNull()
    {
        return $this->serializeNull;
    }

    /**
     * @param bool $serializeNull
     *
     * @return CEFData
     */
    public function setSerializeNull($serializeNull)
    {
        $this->serializeNull = $serializeNull;

        return $this;
    }

    /**
     * To JSON.
     *
     * @return string
     */
    public function toJson()
    {
        return $this->serialize($this, 'json');
    }

    /**
     * Serialize.
     *
     * @param $obj
     * @param string $type
     *
     * @return mixed|string
     */
    protected function serialize($obj, $type = 'json')
    {
        $context = new SerializationContext();
        $context->setSerializeNull($this->isSerializeNull());

        $builder = SerializerBuilder::create();
        $builder
            ->configureHandlers(function (HandlerRegistry $registry) {

                // Entity Array Handler
                $registry->registerHandler('serialization', 'EntityCollection', 'json',
                    function ($visitor, EntityCollection $obj, array $type, Context $context) {
                        $nav = $visitor->getNavigator();
                        if (count($obj->getCollection()) < 1) {
                            return true;
                        }

                        return $nav->accept($obj->getCollection(), ['name' => 'array'], $context);
                    }
                );

                // DateTime Handler "Y-m-d H:i:s"
                $registry->registerHandler('serialization', 'DateTime', 'json',
                    function ($visitor, \DateTime $obj, array $type, Context $context) {
                        return date('Y-m-d H:i:s', $obj->getTimestamp());
                    }
                );
            });

        $serializer = $builder->build();

        return $serializer->serialize($obj, $type, $context);
    }

    /**
     * Handle attribute sets.
     *
     * Setting properties on the generic EntityModel
     * used for collections without defined models and
     * REQUIRED for hydration of entities with defined models.
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (!$this->hydrate($this, $name, $value)) {
            if (is_object($value) && $value instanceof \stdClass) {
                $value = (array) $value;
            }

            $this->$name = $value;
        }
    }

    /**
     * Hydrate.
     *
     * @param $context
     * @param $name
     * @param $value
     *
     * @return bool
     */
    private function hydrate($context, $name, $value)
    {
        if (is_array($value) || (is_object($value) && $value instanceof \stdClass)) {
            $words = ucwords(str_replace('_', ' ', $name));
            $setter = 'set'.str_replace(' ', '', $words);

            if (method_exists($context, $setter)) {
                $reflection = new ReflectionClass($context);
                $par = $reflection->getMethod($setter)->getParameters();

                $class = $par[0]->getClass();
                if (!$class) {
                    return false;
                }

                $class = $class->name;
                $obj = new $class();

                // if NOT type of EntityCollection we are done
                if (!$obj instanceof EntityCollection) {
                    $obj = $this->hydrateClassObject($obj, $value);
                    $context->$name = $obj;

                    return true;
                }

                $model = null;
                $entities = [];

                foreach ($value as $k => $v) {
                    $valueClass = $obj->getValueClass();
                    $model = new $valueClass();
                    $entities[$k] = $this->hydrateClassObject($model, $v);
                }

                $obj->setCollection($entities);

                $context->$name = $obj;

                return true;
            }
        }
    }

    /**
     * Hydrate class object.
     *
     * @param $obj
     * @param $data
     *
     * @return mixed
     */
    private function hydrateClassObject($obj, $data)
    {
        foreach ($data as $k => $v) {
            // recursion
            if (!$this->hydrate($obj, $k, $v)) {
                $obj->$k = $v;
            }
        }

        return $obj;
    }
}
