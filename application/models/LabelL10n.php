<?php

/**
 * LimeSurvey (tm)
 * Copyright (C) 2011 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

/**
 * Class Label
 *
 * @property integer $id Primary Key
 * @property integer $label_id Related Label ID
 * @property string $title title
 * @property string $language connected language
 */
class LabelL10n extends LSActiveRecord
{
    /**
     * Used for some statistical queries
     *
     * @var int
     */
    public $maxsortorder;

    /**
     * Returns the table name of this model.
     *
     * @inheritdoc
     *
     * @return string
     */
    public function tableName()
    {
        return '{{label_l10ns}}';
    }

    /**
     * Returns the primary key of this model.
     *
     * @inheritdoc
     *
     * @return string
     * */
    public function primaryKey()
    {
        return 'id';
    }

    /**
     * Returns the static model of the specified AR class.
     *
     * @param $className Classname
     *
     * @inheritdoc
     *
     * @return LabelL10n
     */
    public static function model($className = __CLASS__)
    {
        /**
         * Model
         *
         *  @var self $model
         */
        $model = parent::model($className);
        return $model;
    }

    /**
     * Returns the validation rules of this model.
     *
     * @inheritdoc
     *
     * @return array
     */
    public function rules()
    {
        return array(
            array('label_id', 'numerical', 'integerOnly' => true),
            array('title', 'LSYii_Validators'),
            array('language', 'length', 'min' => 2, 'max' => 20), // in array languages ?
        );
    }

    /**
     * Returns the relations of this model.
     *
     * @inheritdoc
     *
     * @return array
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'label' => array(self::BELONGS_TO, 'Label', 'label_id')
        );
    }

    /**
     * Defines the default query scope for this ActiveRecord.
     *
     * This scope indexes the returned records by their language code.
     *
     * @return array The default scope configuration.
     */
    public function defaultScope()
    {
        return array('index' => 'language');
    }
}
