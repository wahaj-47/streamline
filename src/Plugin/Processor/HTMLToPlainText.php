<?php

namespace Drupal\streamline\Plugin\Processor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use DOMDocument;

/**
 * Plugin implementation of the 'HTMLToPlainText' processor.
 *
 * @Processor(
 *   id = "html_to_plaintext",
 *   label = @Translation("HTML to Plain Text"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class HTMLToPlainText extends PluginBase implements ProcessorInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        if (empty($value)) {
            return "";
        }

        if (is_array($value)) {
            return array_map([$this, 'toPlainText'], $value);
        }

        return $this->toPlainText($value);
    }

    private function toPlainText($value)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($value, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $text = $this->extractTextFromNode($dom->documentElement);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(chr(160), ' ', $text);
        $text = preg_replace('/[^\x20-\x7E\t\n\r]/', '', $text);
        $text = preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/',
            function ($matches) {
                return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UTF-16BE');
            },
            $text
        );

        return trim($text);
    }

    private function extractTextFromNode($node)
    {
        $text = "";

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= trim($child->textContent) . " ";
            }
            if ($child->nodeType === XML_ELEMENT_NODE) {
                switch ($child->nodeName) {
                    case 'a':
                        $anchor_text = trim($child->textContent);
                        $href = $child->getAttribute("href");
                        if (!empty($anchor_text) && !empty($href)) {
                            $text .= $anchor_text . " (" . $href . ") ";
                        }
                        break;

                    case 'li':
                        $text .= "- " . $this->extractTextFromNode($child) . "\n";
                        break;

                    case 'ul':
                    case 'ol':
                        $text .= "\n" . $this->extractTextFromNode($child) . "\n";
                        break;

                    default:
                        $text .= $this->extractTextFromNode($child);
                        break;
                }
            }
        }

        return $text;
    }
}
