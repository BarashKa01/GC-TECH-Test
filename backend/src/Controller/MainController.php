<?php

namespace App\Controller;

use App\Entity\DataSet;
use App\Repository\DataSetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class MainController extends AbstractController
{
    /**
     * This function fill the database to have some datasets
     * Avoid scraping just for the example
     * Should make a scraping API
     *
     * @return JsonResponse
     */
    #[Route('/', name: 'app_main')]
    public function index(EntityManagerInterface $entityManager,
    DataSetRepository $dataSetRepository, LoggerInterface $logger, Request $request): JsonResponse
    {   
        $activitiesTypeFromDataBase = $dataSetRepository->findAll();

        if(!$activitiesTypeFromDataBase) {
            $logger->info('Filling database from google maps...');
            $this->fillDatabase($entityManager);
            $activitiesTypeFromDataBase = $dataSetRepository->findAll();
            $logger->info('Filling database complete !');
        } else {
            $logger->info('Database already filled !');
        }

        $searchCriterias = explode('%s', $request->query->get('query'));

        $results = $this->search($searchCriterias, $activitiesTypeFromDataBase);
        
        return $this->json($results);
    }

    private function fillDatabase(EntityManagerInterface $entityManager) {

        $activitiesTypes = ['activités', 'aventures', 'randonnées'];
    
        for ($i = 0; $i < count($activitiesTypes); ++$i) {
            $url = "https://www.searchapi.io/api/v1/search";
            $params = array(
                "engine" => "google_maps",
                "q" => $activitiesTypes[$i],
                "ll" => "@43.6342046,3.7689112,11z",
                "api_key" => "TheRemovedAPIKey"
            );
            $queryString = http_build_query($params);

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url . '?' . $queryString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "accept: application/json"
                ]
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                echo "cURL Error #:" . $error;
            } else {
                $dataSet = new DataSet();
                $dataSet->setActivity($activitiesTypes[$i]);
                $dataSet->setJson(json_decode($response, true));

                $entityManager->persist($dataSet);
                $entityManager->flush($dataSet);
            }
        }
    }

    private function search(array $searchCriterias, array $activities): mixed {
        $results = [];

        //Since the data model isn't very well formated,
        //we need to loop more than needed with a good data modeling
        foreach($activities as $activity) {
            
            //Get the json array from each activity type
            $activitiesFromType = $activity->getJson();

            foreach($activitiesFromType["local_results"] as $activityFromType) {

                foreach($searchCriterias as $searchCriteria) {
                    //This can be much more refined, it's a "OR" search to get results widely
                    //Should use str_contains, but it's mean that we need to lowercase all data (search and database data)
                    //Should use doctrine query for this, but data are not well formated
                    if(mb_stripos($activityFromType['title'], $searchCriteria) ||
                     (key_exists('address', $activityFromType) && mb_stripos($activityFromType['address'], $searchCriteria)) ||
                     mb_stripos($activityFromType['type'], $searchCriteria)) {
                        array_push($results, $activityFromType);
                    }
                }
            }
        }
        return $results;
    }
}
