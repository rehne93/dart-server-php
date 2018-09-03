<?php

namespace App\Controller;

use App\Forms\Game170Form;
use App\model\Dart170;
use DateTime;
use Game;
use Map\GameTableMap;
use Player;
use PlayerQuery;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;


class Dart170FormController extends AbstractController
{

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private $logger;
    private $playerName;

    /**
     * @Route("/dart/170", name="dart170_form")
     */
    public function new(Request $request)
    {
        $cookie = $request->cookies;
        if ($cookie->has('player')) {
            $this->logger->debug("Found Player");
            $this->playerName = $cookie->get('player');
        } else {
            return $this->redirectToRoute('new_player');
        }
        $dartStats = new Dart170();
        $dartStats->setDate(new DateTime('today'));
        $dartStats->setNumRounds(0);

        $form = $this->createForm(Game170Form::class, $dartStats);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleData($form);
        }

        $response = $this->render('dart170_form/index.html.twig', array('name' => $this->playerName, 'form' => $form->createView()));
        return $response;
    }


    private function handleData($form)
    {
        $dartStats = $form->getData();
        if ($dartStats->getNumRounds() == 0) {
            return $this->render('dart170_form/index.html.twig', array('form' => $form->createView()));
        }
        $this->logger->info("Form Rounds: " . $dartStats->getNumRounds());
        $playerQuery = new PlayerQuery();
        $player = $playerQuery->findByName($this->playerName)->getFirst();
        if ($player === null) {
            $this->logger->debug("Player: " . $this->playerName . " not found.");
            return $this->redirectToRoute('new_player');
        } else {
            $this->logger->debug("Player found.");
        }
        $game = new Game();
        $game->setGametype("170");
        $game->setRounds($dartStats->getNumRounds());
        $game->setDate($dartStats->getDate());
        $game->setPlayer($player);
        try {
            $game->save();
            $this->addFlash('success', "Saved game");
        } catch (PropelException $e) {
        }
    }
}