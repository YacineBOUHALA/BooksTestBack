<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    #[Route('/api/books', name: 'book_list')]
    public function index(Request $request, BookRepository $bookRepository): JsonResponse
    {
        try {
            $title = $request->query->get('title');
            $category = $request->query->get('category');
            $publishedYear = $request->query->get('publication_year');

            $book = $bookRepository->findWithFilter($title, $category, $publishedYear);

            return $this->json($book, 200, [], ["groups" => "book:read"]);
        } catch (\Exception $e) {
            // Log the exception for debugging
            error_log($e->getMessage());
            return $this->json(null, 200, [], ["groups" => "book:read"]);
        }
    }

    #[Route('/api/books/{id}', name: 'book')]
    public function getOneBook(int $id, BookRepository $bookRepository): JsonResponse
    {
        $book = $bookRepository->find($id);
        return $this->json($book, 200, [ 'Access-Control-Allow-Origin'=> '*'], ["groups" => "book:read"]);
    }

    #[Route('/api/booking/create', name: 'booking_create', methods: ['POST'])]
    public function create(
        Request $request,
        BookRepository $bookRepository,
        BookingRepository $bookingRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $requestData = json_decode($request->getContent(), true);

            if (!isset($requestData['book_id'], $requestData['user_id'], $requestData['start_date'], $requestData['end_date'])) {
                return $this->json(['error' => 'Missing required data'], 400);
            }

            $book = $bookRepository->find($requestData['book_id']);
            $user = $userRepository->find($requestData['user_id']);

            if (!$book || !$user) {
                return $this->json(['error' => 'Book or user not found'], 404);
            }

            $startDate = new \DateTimeImmutable($requestData['start_date']);
            $endDate = new \DateTimeImmutable($requestData['end_date']);
            $existingBooking = $bookingRepository->findOverlappingBooking($book, $startDate, $endDate);

            if ($existingBooking) {

                if ($existingBooking->getStatus() == 'active') {
                    return $this->responseMessage(400, "Book is already reserved for the specified period");

                } elseif ($existingBooking->getStatus() == 'canceled') {
                    $existingBooking->setStartDate($startDate);
                    $existingBooking->setEndDate($endDate);
                    $existingBooking->setStatus('active');
                    $entityManager->persist($existingBooking);
                    $entityManager->flush();

                    return $this->responseMessage(201, "Booking updated successfully", $existingBooking);
                }
            }

            $booking = new Booking();
            $booking->setBook($book);
            $booking->setUser($user);
            $booking->setStartDate($startDate);
            $booking->setEndDate($endDate);
            $booking->setStatus('active');
            $entityManager->persist($booking);
            $entityManager->flush();
            return $this->responseMessage(201, "Booking created successfully", $booking);

        } catch (\Exception $e) {
            error_log($e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }


    #[Route('/api/booking/cancel', name: 'booking_cancel', methods: ['PUT'])]
    public function cancel(Request $request, BookRepository $bookRepository, BookingRepository $bookingRepository, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        if (!isset($requestData['book_id'], $requestData['user_id'], $requestData['start_date'], $requestData['end_date'])) {
            return $this->json(['error' => 'Missing required data'], 400);
        }

        $book = $bookRepository->find($requestData['book_id']);
        $user = $userRepository->find($requestData['user_id']);

        if (!$book || !$user) {
            return $this->json(['error' => 'Book or user not found'], 404);
        }

        $startDate = new \DateTimeImmutable($requestData['start_date']);
        $endDate = new \DateTimeImmutable($requestData['end_date']);
        $existingBooking = $bookingRepository->findOverlappingBooking($book, $startDate, $endDate);

        if ($existingBooking->getStatus()=="active" ) {
            $existingBooking->setStatus('canceled');
            $entityManager->flush();
            return $this->responseMessage(200, "Booking canceled successfully", $existingBooking);

        }
        return $this->json(['error' => 'Book is not booked yet'], 400);

    }


    public function responseMessage(int $status, string $message, Booking $booking = null ){
        return $this->json(
            [
                'message' => $message,
                "booking" => $booking
            ],
            $status,
            [ 'Access-Control-Allow-Origin'=> '*'],
            [
                "groups" => ["booking", "user", "book:read"]
            ]
        );

    }

}
