<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @return Book[] Returns an array of Book objects
     */
    public function findWithFilter(?string $title = null, ?string $category = null, ?string $publishedYear = null)
    {
        $qb = $this->createQueryBuilder('b');

        if ($title !== null && $title != "") {
            $qb->andWhere('b.title = :title')
                ->setParameter('title', $title);
        }

        if ($category !== null && $category != "") {
            $qb->andWhere('b.category = :category')
                ->setParameter('category', $category);
        }

        if ($publishedYear !== null && $publishedYear != "") {
            $startDate = new \DateTime("$publishedYear-01-01 00:00:00");
            $endDate = new \DateTime("$publishedYear-12-31 23:59:59");

            $qb->andWhere('b.publishedAt >= :startDate')
                ->setParameter('startDate', $startDate)
                ->andWhere('b.publishedAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }
        if($title == null && $category == null && $publishedYear == null) {
            return $qb->select('b')->getQuery()->getResult();
        }

        return $qb->getQuery()->getResult();
    }



    //    /**
    //     * @return Book[] Returns an array of Book objects
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

    //    public function findOneBySomeField($value): ?Book
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
