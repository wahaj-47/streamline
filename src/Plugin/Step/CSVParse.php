<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Parse as CSV' step.
 *
 * @Step(
 *   id = "csv-parse",
 *   label = @Translation("Parse as CSV"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class CSVParse extends StepBase implements StepInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);
        $form['filepath'] = [
            '#type' => 'textfield',
            '#title' => t('Output File Path'),
            '#description' => t('Example: public://csv_files/'),
            '#default_value' => $this->configuration['filepath'] ?? 'public://streamline/',
        ];

        $form['filename'] = [
            '#type' => 'textfield',
            '#title' => t('Output File Name'),
            '#description' => t('File extension .csv will be added automatically.'),
            '#default_value' => $this->configuration['filename'] ?? 'Streamlined',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(FormStateInterface $form_state, $parent)
    {
        parent::save($form_state, $parent);
        $current_filepath = $form_state->getValue(array_merge($parent, ['filepath']));
        $current_filename = $form_state->getValue(array_merge($parent, ['filename']));
        $current_file = $this->sanitizeFilePath($current_filepath) . $this->sanitizeFileName($current_filename);

        $saved_filepath = $this->configuration['filepath'] ?? 'public://streamline/';
        $saved_filename = $this->configuration['filename'] ?? 'Streamlined';
        $saved_file = $this->sanitizeFilePath($saved_filepath) . $this->sanitizeFileName($saved_filename);

        if ($current_file == $saved_file) return;

        // Delete the old file
        $file_storage = \Drupal::entityTypeManager()
            ->getStorage('file');
        $query = $file_storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('uri', $saved_file);

        $fid = $query->execute();
        if (!empty($fid)) {
            /** @var \Drupal\file\Entity\File $file */
            $file = $file_storage->load(reset($fid));
            try {
                $file->delete();
            } catch (\Exception $e) {
                \Drupal::service("messenger")->addMessage(t('Failed deleting managed file %uri. Result was %result', [
                    '%uri' => $saved_file,
                    '%result' => print_r($e->getMessage(), TRUE),
                ]), 'error');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute($input = NULL)
    {
        if (empty($input)) {
            \Drupal::logger('streamline')->error('CSV Parse error: $input is empty');
            return NULL;
        }

        $filepath = $this->configuration['filepath'];
        $filepath = $this->sanitizeFilePath($filepath);

        $filename = $this->configuration['filename'];
        $filename = $this->sanitizeFileName($filename);

        $full_path = $filepath . $filename;

        $all_keys = [];
        foreach ($input as $row) {
            $all_keys = array_unique(array_merge($all_keys, array_keys($row)));
        }

        // Create a CSV string
        $output = fopen('php://temp', 'w+');
        fputcsv($output, $all_keys);
        foreach ($input as $row) {
            // Align the row to match the header keys.
            $aligned_row = array_merge(array_fill_keys($all_keys, ''), $row);
            // Ensure the order matches the header.
            $aligned_row = array_intersect_key($aligned_row, array_flip($all_keys));
            fputcsv($output, $aligned_row);
        }
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        // Prepare the directory.
        $file_system = \Drupal::service('file_system');
        if (!$file_system->prepareDirectory($filepath, FileSystemInterface::MODIFY_PERMISSIONS | FileSystemInterface::CREATE_DIRECTORY)) {
            \Drupal::logger('streamline')->error('Failed to prepare directory: %directory', ['%directory' => $filepath]);
            \Drupal::messenger()->addError(t('Failed to prepare directory.'));
            return NULL;
        }

        try {
            // Write the file.
            $file = \Drupal::service('file.repository')->writeData(
                $csv_content,
                $full_path,
                FileExists::Replace
            );

            if ($file) {
                \Drupal::messenger()->addMessage(t('Saved file as %filename', ['%filename' => $file->getFileUri()]));
                return [];
            } else {
                \Drupal::logger('streamline')->error('Failed to save file: %filename', ['%filename' => $full_path]);
                \Drupal::messenger()->addError(t('Failed to save file.'));
                return NULL;
            }
        } catch (\Exception $e) {
            \Drupal::logger('streamline')->error('Exception occurred while saving file: @message', ['@message' => $e->getMessage()]);
            \Drupal::messenger()->addError(t('An error occurred while saving the file.'));
            return NULL;
        }
    }

    private function sanitizeFilePath($filepath)
    {
        if (!str_starts_with($filepath, 'public://')) $filepath = 'public://' . $filepath;
        if (!str_ends_with($filepath, '/')) $filepath .= "/";

        return $filepath;
    }

    private function sanitizeFileName($filepath)
    {
        if (!str_ends_with($filepath, '.csv')) $filepath .= ".csv";

        return $filepath;
    }
}
