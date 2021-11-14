<?php

namespace Src;

use Phpml\Clustering\KMeans;
use Phpml\Classification\SVC;
use Phpml\Dataset\CsvDataset;
use Phpml\Classification\NaiveBayes;
use Phpml\SupportVectorMachine\Kernel;


class Computer
{
    public function train()
    {
        $dataset = new CsvDataset('dataset.csv', 15, true);

        $samples = $dataset->getSamples();
        $labels = $dataset->getTargets();

        //    $classifier = new SVC(Kernel::RBF, $cost = 1000, $degree = 3, $gamma = 6);



        $classifier = new SVC(
            Kernel::LINEAR, // $kernel
            1.0,            // $cost
            3,              // $degree
            null,           // $gamma
            0.0,            // $coef0
            0.001,          // $tolerance
            100,            // $cacheSize
            true,           // $shrinking
            true            // $probabilityEstimates, set to true
        );

        // $classifier = new NaiveBayes();


        $classifier->train($samples, $labels);



        // print_r($classifier->predictProbability([3, 4]));
        print_r($classifier->predict([1, 2, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]));


        //$kmeans = new KMeans(25);
        // print_r($kmeans->cluster($samples));
    }
}
