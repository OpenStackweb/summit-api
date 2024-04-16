<?php namespace App\Models\Foundation\ExtraQuestions;
/**
 * Copyright 2021 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/


/**
 * interface ExtraQuestionTypeConstants
 * @package App\Models\Foundation\ExtraQuestions
 */
interface ExtraQuestionTypeConstants
{
    const TextAreaQuestionType        = 'TextArea';
    const TextQuestionType            = 'Text';
    const CheckBoxQuestionType        = 'CheckBox';
    const RadioButtonQuestionType     = 'RadioButton';
    const ComboBoxQuestionType        = 'ComboBox';

    const CountryComboBoxQuestionType = 'CountryComboBox';
    const CheckBoxListQuestionType    = 'CheckBoxList';
    const RadioButtonListQuestionType = 'RadioButtonList';

    const ValidQuestionTypes = [
        self::TextAreaQuestionType,
        self::TextQuestionType,
        self::CheckBoxQuestionType,
        self::ComboBoxQuestionType,
        self::CheckBoxListQuestionType,
        self::RadioButtonListQuestionType,
        self::CountryComboBoxQuestionType,
    ];

    const AllowedMultiValueQuestionType = [
        self::ComboBoxQuestionType,
        self::CheckBoxListQuestionType,
        self::RadioButtonListQuestionType,
        self::CountryComboBoxQuestionType,
    ];

    const AllowedPlaceHolderQuestionType = [
        self::TextAreaQuestionType,
        self::TextQuestionType,
    ];

    const QuestionClassMain = 'MainQuestion';
    const QuestionClassSubQuestion = 'SubQuestion';

    const AllowedQuestionClass = [
        self::QuestionClassMain,
        self::QuestionClassSubQuestion,
    ];

    const SubQuestionRuleVisibility_Visible = 'Visible';
    const SubQuestionRuleVisibility_NotVisible = 'NotVisible';

    const AllowedSubQuestionRuleVisibility = [
        self::SubQuestionRuleVisibility_Visible,
        self::SubQuestionRuleVisibility_NotVisible
    ];

    const SubQuestionRuleVisibilityCondition_Equal = 'Equal';
    const SubQuestionRuleVisibilityCondition_NotEqual = 'NotEqual';

    const AllowedSubQuestionRuleVisibilityCondition = [
        self::SubQuestionRuleVisibilityCondition_Equal,
        self::SubQuestionRuleVisibilityCondition_NotEqual,
    ];

    const SubQuestionRuleAnswerValuesOperator_And= 'And';
    const SubQuestionRuleAnswerValuesOperator_Or= 'Or';

    const AllowedSubQuestionRuleAnswerValuesOperator = [
        self::SubQuestionRuleAnswerValuesOperator_And,
        self::SubQuestionRuleAnswerValuesOperator_Or,
    ];

    const AnswerCharDelimiter = ',';
}