<?php

namespace App\Command;

use App\Document\ModerationState;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElementsRemoveOptionsCommand extends GoGoAbstractCommand
{
    protected function gogoConfigure(): void
    {
        $this
        ->setName('app:elements:removeOptions')
        ->addArgument('ids', InputArgument::REQUIRED, 'ids to remove')
       ;
    }

    protected function gogoExecute(DocumentManager $dm, InputInterface $input, OutputInterface $output): void
    {
        try {
            $this->log('Elements remove options begin, options ids to remove : '.$input->getArgument('ids'));
            $optionsIdDeleted = array_map(function ($string) { return (int) $string; }, explode(',', $input->getArgument('ids')));

            $qb = $dm->query('Element');
            $elements = $qb->field('optionValues.optionId')->in($optionsIdDeleted)->execute();
            $this->log($elements->count().' element to proceed');

            if ($elements->count() > 0) {
                $i = 0;
                foreach ($elements as $element) {
                    $optionsValues = $element->getOptionValues()->toArray();
                    $optionValuesToBeRemoved = array_filter($optionsValues, function ($oV) use ($optionsIdDeleted) { return in_array($oV->getOptionId(), $optionsIdDeleted); });
                    foreach ($optionValuesToBeRemoved as $key => $optionValue) {
                        $element->removeOptionValue($optionValue);
                    }

                    if (0 == count($element->getOptionValues())) {
                        $element->setModerationState(ModerationState::NoOptionProvided);
                    }

                    if (0 == (++$i % 100)) {
                        $dm->flush();
                        $dm->clear();
                    }
                }
                $dm->flush();
                $dm->clear();
            }

            $this->log('All options successfully removed');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
