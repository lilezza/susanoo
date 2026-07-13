<?php


declare(strict_types=1);

require_once __DIR__ . '/BaseHandler.php';

final class CategoriesHandler extends BaseHandler
{
    public function handle(): void
    {
        $this->requireMethod('GET');

        if (($this->setting['statuscategorygenral'] ?? '') === 'offcategorys') {
            SusanooResponse::ok([]);
        }

        $codePanel = $this->resolveCountryId();
        if ($codePanel === '') {
            SusanooResponse::badRequest('country_id is required');
        }
        $panel = $this->loadPanelByCode($codePanel);

        $allCategories = SusanooDb::fetchAll('SELECT * FROM category');

        $userAgent = $this->user['agent'] ?? 'f';
        $list = [];
        foreach ($allCategories as $cat) {
            $count = (int) SusanooDb::fetchScalar(
                "SELECT COUNT(*) FROM product
                  WHERE (Location = :location OR Location = '/all')
                    AND category = :category
                    AND (agent = :agent OR agent = 'all')",
                [
                    ':location' => $panel['name_panel'],
                    ':category' => $cat['remark'],
                    ':agent'    => $userAgent,
                ]
            );
            if ($count === 0) continue;

            $list[] = [
                'id' => $cat['id'],
                'name' => $cat['remark'],
            ];
        }

        SusanooResponse::ok($list);
    }
}

