<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Expr\Array_;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findOverlappingBooking(Book $book, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): ?Booking
    {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.book = :book')
            ->setParameter('book', $book)
            ->andWhere('b.startDate < :end_date')
            ->setParameter('end_date', $endDate)
            ->andWhere('b.endDate > :start_date')
            ->setParameter('start_date', $startDate);


        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findOneBooking(Book $book, User $user): Array
    {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.book = :book')
            ->setParameter('book', $book)
            ->andWhere('b.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }



//    /**
//     * @return Booking[] Returns an array of Booking objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Booking
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
