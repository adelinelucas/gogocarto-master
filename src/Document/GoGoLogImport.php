<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Special log for Import.
 *
 * @MongoDB\Document
 */
class GoGoLogImport extends GoGoLog
{
    public function displayMessage(TranslatorInterface $t = null)
    {
        $result = $this->getMessage().' ! <strong>'.$t->trans('importService.total', ['count' => $this->getDataProp('elementsCount')], 'admin').'</strong> - ';

        $fields = ['elementsCreatedCount', 'elementsUpdatedCount', 'elementsNothingToDoCount',
                   'elementsMissingGeoCount', 'elementsMissingTaxoCount', 'elementsPreventImportedNoTaxo',
                   'elementsDeletedCount', 'elementsErrorsCount', 'automaticMergesCount', 'potentialDuplicatesCount'];
        $messages = [];
        foreach($fields as $field) {
            if ($this->getDataProp($field) > 0) {
                $messages[] = $t->trans("importService.$field", ['count' => $this->getDataProp($field)], 'admin');
            }
        }
        $result .= implode(' - ', $messages);
        
        if ($this->getDataProp('errorMessages')) {
            $result .= '</br></br>'.implode('</br>', $this->getDataProp('errorMessages'));
        }

        return $result;
    }
}
