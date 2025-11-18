<?php

/**
 * LimeSurvey
 * Copyright (C) 2007-2017 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

/**
 * Class Answer
 *
 * @property integer $aid PK
 * @property integer $qid Question id
 * @property string $code Answer code
 * @property integer $sortorder Answer sort order
 * @property integer $assessment_value
 * @property integer $scale_id
 *
 * @property Question $question
 * @property Question $group
 * @property AnswerL10n[] $answerl10ns
 */
class Answer extends LSActiveRecord
{
    private $oldCode;
    private $oldQid;
    private $oldScaleId;

    /**
     *  Returns the static model of the specified AR class.
     *
     * @param $className Classname
     *
     * @inheritdoc
     *
     * @return static
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
     * Returns the table name of this model.
     *
     * @inheritdoc
     *
     * @return string
     */
    public function tableName()
    {
        return '{{answers}}';
    }

    /**
     * Returns the primary key of this model.
     *
     * @inheritdoc
     *
     * @return string
     */
    public function primaryKey()
    {
        return 'aid';
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
        $alias = $this->getTableAlias();
        return array(
            'question' => array(self::BELONGS_TO, 'Question', '',
                'on' => "$alias.qid = question.qid",
            ),
            'group' => array(self::BELONGS_TO, 'QuestionGroup', '', 'through' => 'question',
                'on' => 'question.gid = ' . Yii::app()->db->quoteTableName('group') . '.gid'
            ),
            'answerl10ns' => array(self::HAS_MANY, 'AnswerL10n', 'aid', 'together' => true),
            'questionl10ns' => array(self::HAS_MANY, 'QuestionL10n', 'qid', 'together' => true)

        );
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
            array('qid', 'numerical', 'integerOnly' => true),
            array('code', 'length', 'min' => 1, 'max' => 5),
            array('code', 'required'),
            // Only alphanumeric
            array(
                'code',
                'match',
                'pattern' => '/^[[:alnum:]]*$/',
                'message' => gT('Answer codes may only contain alphanumeric characters.'),
            ),
            // Unicity of key
            array(
                'code',
                'checkUniqueness',
                'message' => gT('Answer codes must be unique by question.'),
                'except' => 'saveall'
            ),
            array('sortorder', 'numerical', 'integerOnly' => true, 'allowEmpty' => true),
            array('assessment_value', 'numerical', 'integerOnly' => true, 'allowEmpty' => true),
            array('scale_id', 'numerical', 'integerOnly' => true, 'allowEmpty' => true),
        );
    }

    /**
     * This defaultScope indexes the ActiveRecords given back by attribute name.
     *
     * @return array
     */
    public function defaultScope()
    {
        return array(
            'order' => 'sortorder, code'
        );
    }

    /**
     * Returns the answers.
     *
     * @param integer $qid Question ID
     *
     * @return CDbDataReader
     */
    public function getAnswers($qid)
    {
        // TODO get via Question relations
        return Yii::app()->db->createCommand()
            ->select()
            ->from(self::tableName())
            ->where(array('and', 'qid=' . $qid))
            ->order('code asc')
            ->query();
    }

    /**
     * Validates that the answer code is unique within the same question and scale.
     *
     * If the code, question ID, or scale ID has changed compared to the original
     * values, a lookup is performed to ensure no duplicate exists.
     *
     * @return void
     * @todo   Refactor this function for better readability.
     */
    public function checkUniqueness()
    {
        if ($this->code !== $this->oldCode || $this->qid != $this->oldQid || $this->scale_id != $this->oldScaleId) {
            $model = self::model()->find('code = ? AND qid = ? AND scale_id = ?', array($this->code, $this->qid, $this->scale_id));
            if ($model != null) {
                $this->addError('code', 'Answer codes must be unique by question');
            }
        }
    }

    /**
     * After Find.
     *
     * @return void
     */
    protected function afterFind()
    {
        parent::afterFind();
        $this->oldCode = $this->code;
        $this->oldQid = $this->qid;
        $this->oldScaleId = $this->scale_id;
    }

    /**
     * Return the key=>value answer for a given $qid
     *
     * @param integer $qid       Question ID
     * @param string  $code      Question Code
     * @param string  $sLanguage Language
     * @param integer $iScaleID  Scale
     *
     * @return string|null The answer text
     */
    public function getAnswerFromCode($qid, $code, $sLanguage, $iScaleID = 0)
    {
        static $answerCache = array();

        if (
            array_key_exists($qid, $answerCache)
            && array_key_exists($code, $answerCache[$qid])
            && array_key_exists($sLanguage, $answerCache[$qid][$code])
            && array_key_exists($iScaleID, $answerCache[$qid][$code][$sLanguage])
        ) {
            // We have a hit :)
            return $answerCache[$qid][$code][$sLanguage][$iScaleID];
        } else {
            $aAnswer = Answer::model()->findByAttributes(array('qid' => $qid, 'code' => $code, 'scale_id' => $iScaleID));
            if (is_null($aAnswer)) {
                return null;
            }
            if (!isset($aAnswer->answerl10ns[$sLanguage])) {
                Yii::log("AnswerL10n record missing for language \"{$sLanguage}\" and aid {$aAnswer->aid}", 'warning', 'application.models.Answer.getAnswerFromCode');
                return null;
            }
            $answerCache[$qid][$code][$sLanguage][$iScaleID] = $aAnswer->answerl10ns[$sLanguage]->answer;
            return $answerCache[$qid][$code][$sLanguage][$iScaleID];
        }
    }

    /**
     * Finds all answers for a given new survey ID that still reference
     * an old survey ID via an {INSERTANS::...} tag.
     *
     * @param int $newsid The new survey ID.
     * @param int $oldsid The old survey ID referenced in the INSERTANS tag.
     *
     * @return static[] Matching records indexed according to the model's default settings.
     * @todo   Rename this method and its parameters to more descriptive names,
     *   e.g. findInsertAnsReferences($newSid, $oldSid), while keeping the
     *   current signature temporarily for backward compatibility.
     */
    public function oldNewInsertansTags($newsid, $oldsid)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('question.sid', $newsid);
        $criteria->with = ['answerl10ns' => array('condition' => "answer like '%{INSERTANS::{$oldsid}X%'"), 'question'];
        return $this->findAll($criteria);
    }

    /**
     * Updates records in the table for the given condition.
     *
     * If no condition is provided, all rows in the table will be updated.
     *
     * @param array              $data      Column-value pairs to be updated.
     * @param string|array|false $condition Optional WHERE condition. Can be a string,
     *                                      an array (Yii format) or false to update
     *                                      all rows.
     *
     * @return int Number of affected rows.
     *
     * @todo Consider splitting into a strict variant that requires a condition to avoid accidental full-table updates.
     */
    public function updateRecord($data, $condition = false)
    {
        return Yii::app()->db->createCommand()->update(self::tableName(), $data, $condition ? $condition : '');
    }

    /**
     * Inserts records in the table.
     *
     * @param array $data Column-value pairs to be updated.
     *
     * @return     boolean|null
     * @deprecated at 2018-01-29 use $model->attributes = $data && $model->save()
     */
    public function insertRecords($data)
    {
        $oRecord = new self();
        foreach ($data as $k => $v) {
            $oRecord->$k = $v;
        }
        if ($oRecord->validate()) {
            return $oRecord->save();
        }
        Yii::log(\CVarDumper::dumpAsString($oRecord->getErrors()), 'warning', 'application.models.Answer.insertRecords');
        return null;
    }

    /**
     * Updates sort order of answers inside a question
     *
     * @param int $qid Question ID
     *
     * @return void
     * @static
     * @access public
     */
    public static function updateSortOrder($qid)
    {
        $data = self::model()->findAllByAttributes(array('qid' => $qid), array('order' => 'sortorder, code'));
        $position = 0;

        foreach ($data as $row) {
            $row->sortorder = $position++;
            $row->save();
        }
    }

    /**
     * Return answers for statistics.
     *
     * @param string $fields    Fields
     * @param mixed  $condition Condition
     * @param string $orderby   OrderBy
     *
     * @return array
     */
    public function getAnswersForStatistics($fields, $condition, $orderby)
    {
        return Answer::model()->with('answerl10ns')->findAll(['condition' => $condition, 'order' => $orderby]);
    }

    /**
     * Returns questions for statistics.
     *
     * @param string $fields    Fields
     * @param mixed  $condition Condition
     * @param string $orderby   OrderBy
     *
     * @return array
     */
    public function getQuestionsForStatistics($fields, $condition, $orderby)
    {
        $oAnswers = Answer::model()->with('answerl10ns')->findAll(['condition' => $condition,'order' => $orderby]);
        $arr = array();
        foreach ($oAnswers as $key => $answer) {
            $arr[$key] = array_merge($answer->attributes, current($answer->answerl10ns)->attributes);
        }
        return $arr;
    }
}
