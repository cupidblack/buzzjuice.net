<?php

namespace GiveP2P\P2P\FieldsAPI\Views;

use DOMDocument;
use Give\Framework\FieldsAPI\Field;
use GiveP2P\P2P\FieldsAPI\FieldView;
use GiveP2P\P2P\FieldsAPI\Contracts\FieldViewContract;

/**
 * Class Wrapper
 * Field wrapper template used to wrap all the other fields
 * @package GiveP2P\P2P\FieldsAPI\Views
 *
 * @since 1.0.0
 */
class Wrapper extends FieldViewContract
{

    const PLACEHOLDER_FIELD = '{PLACEHOLDER_FIELD}';
    const PLACEHOLDER_BEFORE_FIELD = '{PLACEHOLDER_BEFORE_FIELD}';
    const PLACEHOLDER_AFTER_FIELD = '{PLACEHOLDER_AFTER_FIELD}';

    /**
     * @inheritDoc
     */
    public function template(Field $field)
    {
        $document = new DOMDocument;

        // Wrapper container
        $wrapper = $document->createElement('div');
        $wrapper->setAttribute('class', 'give-field-wrap');

        if ($field->hasVisibilityConditions()) {
            $wrapper->setAttribute('data-field-visibility', json_encode($field->getVisibilityConditions()));
        }

        // Label container
        $labelContainer = $document->createElement('div');
        $labelContainer->setAttribute('class', 'give-field-label-container');

        // Label
        $label = $document->createElement('label', $field->getLabel());
        $label->setAttribute('for', $field->getId());

        $labelContainer->appendChild($label);

        // Field container
        $fieldContainer = $document->createElement('div');
        $fieldContainer->setAttribute('class', 'give-field-input-container');

        $templateTags = [
            self::PLACEHOLDER_FIELD => FieldView::make($field)->render(),
        ];

        // Render before
        if ($callback = $field->getRenderBeforeCallback()) {
            $fieldContainer->appendChild(
                $document->createTextNode(self::PLACEHOLDER_BEFORE_FIELD)
            );

            $templateTags[self::PLACEHOLDER_BEFORE_FIELD] = call_user_func($callback, $field);
        }

        // Field placeholder
        $fieldContainer->appendChild(
            $document->createTextNode(self::PLACEHOLDER_FIELD)
        );

        // Render after placeholder
        if ($callback = $field->getRenderAfterCallback()) {
            $fieldContainer->appendChild(
                $document->createTextNode(self::PLACEHOLDER_AFTER_FIELD)
            );

            $templateTags[self::PLACEHOLDER_AFTER_FIELD] = call_user_func($callback, $field);
        }

        // Field description
        if (!empty($text = $field->getHelpText())) {
            $fragment = $document->createDocumentFragment();
            $fragment->appendXML(wp_kses_post($text));

            $helpText = $document->createElement('div');
            $helpText->appendChild($fragment);
            $helpText->setAttribute('class', 'give-field-description');

            $fieldContainer->appendChild($helpText);
        }

        // Build
        $wrapper->appendChild($labelContainer);
        $wrapper->appendChild($fieldContainer);

        $document->appendChild($wrapper);

        return str_replace(
            array_keys($templateTags),
            array_values($templateTags),
            $document->saveHTML()
        );
    }
}
