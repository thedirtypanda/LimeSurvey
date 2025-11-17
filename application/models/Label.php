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
 * @property integer $lid Related Label Set
 * @property string $code
 * @property string $title
 * @property integer $sortorder
 * @property integer $assessment_value
 */
class Label extends LSActiveRecord
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
     * @return     string
     */
    public function tableName()
    {
        return '{{labels}}';
    }

    /**
     * Returns the primary key of this model.
     *
     * @inheritdoc
     * @return     string
     */
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
     * @return     Label
     */
    public static function model($className = __CLASS__)
    {
        /**
         * Model
         *
         * @var self $model
         */
        $model = parent::model($className);
        return $model;
    }

    /**
     * Returns the validation rules of this model.
     *
     * @inheritdoc
     * @return     array
     */
    public function rules()
    {
        return array(
            array('lid', 'numerical', 'integerOnly' => true),
            array('code', 'unique', 'caseSensitive' => true, 'criteria' => array(
                            'condition' => 'lid = :lid',
                            'params' => array(':lid' => $this->lid)
                    ),
                    'message' => '{attribute} "{value}" is already in use.'),
            // Only alphanumeric
            array(
                'code',
                'match',
                'pattern' => '/^[[:alnum:]]*$/',
                'message' => gT('Label codes may only contain alphanumeric characters.'),
            ),
            array('sortorder', 'numerical', 'integerOnly' => true, 'allowEmpty' => true),
            array('assessment_value', 'numerical', 'integerOnly' => true, 'allowEmpty' => true),
        );
    }

    /**
     * Returns the relations of this model.
     *
     * @inheritdoc
     * @return     array
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'labelset' => array(self::BELONGS_TO, 'LabelSet', 'lid'),
            'labell10ns' => array(self::HAS_MANY, 'LabelL10n', 'label_id')
        );
    }

    /**
     * Returns the translated attribute set for the given language.
     *
     * @param string $sLanguage Language code (e.g. "de", "en")
     *
     * @return array Merged attribute set for the provided language.
     * @todo   Consider renaming to getTranslatedAttributes() and
     *       unifying fallback behavior. Current implementation
     *       mixes base and translated attributes inconsistently.
     */
    public function getTranslated($sLanguage)
    {
        $ol10N = $this->labell10ns;
        if (isset($ol10N[$sLanguage])) {
            return array_merge($this->attributes, $ol10N[$sLanguage]->attributes);
        }

        return [];
    }

    /**
     * Returns the label code information.
     *
     * @param integer $lid Label ID
     *
     * @return array
     *
     * @todo Please refactor the parameter. int $labelId would be great.
     */
    public function getLabelCodeInfo($lid)
    {
        return Yii::app()->db->createCommand()->select('code, title, sortorder, language, assessment_value')->order('language, sortorder, code')->where('lid=:lid')->from($this->tableName())->bindParam(":lid", $lid, PDO::PARAM_INT)->query()->readAll();
    }

    /**
     * Insert Records.
     *
     * @param $data Data
     *
     * @return     void
     * @deprecated at 2018-02-03 use $model->attributes = $data && $model->save()
     */
    public function insertRecords($data)
    {
        $lbls = new self();
        foreach ($data as $k => $v) {
                    $lbls->$k = $v;
        }
        $lbls->save();
    }
}
