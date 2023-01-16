<?php

namespace App\Application\Library;

class Pagination implements \JsonSerializable
{
    const SIZE = 20;
    const DISPLAY_PAGE_COUNT = 11;

    const OFFSET = 5;

    private int $rows;
    private int $pageSize;
    private int $currentPage;

    /**
     * @param int $currentPage
     * @param int $rows
     * @param int $pageSize
     */
    public function __construct(int $currentPage, int $rows, int $pageSize = self::SIZE)
    {
        $this->currentPage = $currentPage;
        $this->rows = $rows;
        $this->pageSize = $pageSize;
    }


    /**
     * @return int
     */
    public function getTotalPages(): int
    {
        return max(1, (int) ceil($this->rows / $this->pageSize));
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        $min = max($this->currentPage, 1);
        return min($min, $this->getTotalPages());
    }

    /**
     * @return int
     */
    public function getNextPage(): int
    {
        if ($this->getCurrentPage() >= $this->getTotalPages()) {
            return $this->getTotalPages();
        } else {
            return $this->getCurrentPage() + 1;
        }
    }

    /**
     * @return int
     */
    public function getPreviousPage(): int
    {
        if ($this->getCurrentPage() <= 1) {
            return 1;
        } else {
            return $this->getCurrentPage() - 1;
        }
    }

    /**
     * @return array
     */
    public function getDisplayPageNumber(): array
    {
        if ($this->getTotalPages() <= self::DISPLAY_PAGE_COUNT) {
            return range(1, $this->getTotalPages());
        }

        $offsetLeft = $this->getCurrentPage() - self::OFFSET;
        $offsetRight = $this->getCurrentPage() + self::OFFSET;

        $pages = [];
        for ($i = $offsetLeft; $i < $this->getCurrentPage(); $i++) {
            $pages[] = $i;
        }
        for ($i = $this->getCurrentPage(); $i <= $offsetRight; $i++) {
            $pages[] = $i;
        }
        $toRight = 0;
        $toLeft = 0;
        foreach ($pages as $pageNumber) {
            if ($pageNumber <= 0) {
                $toRight++;
            } elseif ($pageNumber > $this->getTotalPages()) {
                $toLeft++;
            }
        }

        if ($toRight > 0) {
            $pages = array_slice($pages, $toRight);
            $lastPage = $pages[count($pages) - 1];
            for ($i = 1; $i <= $toRight; $i++) {
                $pages[] = $lastPage + $i;
            }
        }

        if ($toLeft > 0) {
            $firstPage = $pages[0] - 1;
            $pages = array_slice($pages, 0, 0 - $toLeft);
            for ($i = 1; $i <= $toLeft; $i++) {
                array_unshift($pages, $firstPage - $i);
            }
        }

        if ($pages[0] !== 1) {
            array_unshift($pages, 1);
        }

        if ($pages[1] !== 2) {
            $pages = array_merge(array_slice($pages, 0, 1), ['...'], array_slice($pages, 1));
        }

        if ($pages[count($pages) - 2] !== $this->getTotalPages() - 1) {
            $pages[] = '...';
            $pages[] = $this->getTotalPages();
        } elseif ($pages[count($pages) - 1] !== $this->getTotalPages()) {
            $pages[] = $this->getTotalPages();
        }
        return $pages;
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'currentPage' => $this->getCurrentPage(),
            'previousPage' => $this->getPreviousPage(),
            'nextPage' => $this->getNextPage(),
            'total' => $this->getTotalPages(),
            'pages' => $this->getDisplayPageNumber(),
            'skip' => $this->getSkip(),
            'pageSize' => $this->getPageSize(),
        ];
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @return int
     */
    public function getSkip(): int
    {
        return (int) (($this->getCurrentPage() - 1) * $this->getPageSize());
    }
}
