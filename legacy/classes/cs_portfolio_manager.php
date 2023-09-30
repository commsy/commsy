<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

class cs_portfolio_manager extends cs_manager
{
    public $_user_limit = null;
    public $_template_limit = null;
    public $_delete_limit = true;
    public $_db_table = CS_PORTFOLIO_TYPE;
    public $_sort_order = null;

    private $_translator = null;

    public function cs_announcement_manager($environment)
    {
        parent::__construct($environment);
        $this->_db_table = CS_PORTFOLIO_TYPE;
        $this->_translator = $environment->getTranslationObject();
    }

    public function resetLimits()
    {
        parent::resetLimits();
        $this->_user_limit = null;
        $this->_delete_limit = true;
        $this->_sort_order = null;
        $this->_template_limit = null;
    }

     public function setUserLimit($limit)
     {
         $this->_user_limit = (int) $limit;
     }

     public function setTemplateLimit($limit)
     {
         $this->_template_limit = (int) $limit;
     }

     public function setDeleteLimit($limit)
     {
         $this->_delete_limit = (int) $limit;
     }

     public function setSortOrder($order)
     {
         $this->_sort_order = (string) $order;
     }

     public function _performQuery($mode = 'select')
     {
         if ('count' == $mode) {
             $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
         } elseif ('id_array' == $mode) {
             $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
         } elseif ('distinct' == $mode) {
             $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
         } else {
             $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
         }
         $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('portfolio').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

         $query .= ' WHERE 1';
         if (isset($this->_user_limit)) {
             $query .= ' AND creator_id = "'.encode(AS_DB, $this->_user_limit).'"';
         }
         if (isset($this->_template_limit)) {
             $query .= ' AND template = "'.encode(AS_DB, $this->_template_limit).'"';
         }
         if (true == $this->_delete_limit) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL';
         }
         if (!empty($this->_id_array_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
         }

         if (isset($this->_sort_order)) {
             if ('modified' == $this->_sort_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
             } elseif ('modified_rev' == $this->_sort_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date';
             }
         } else {
             $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
         }
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting portfolio.', E_USER_WARNING);
         } else {
             return $result;
         }
     }

     public function getItem(?int $item_id): ?cs_portfolio_item
     {
         $portfolio_item = null;
         if (!empty($item_id)) {
             if (!empty($this->_cache_object[$item_id])) {
                 return $this->_cache_object[$item_id];
             } elseif (array_key_exists($item_id, $this->_cached_items)) {
                 return $this->_buildItem($this->_cached_items[$item_id]);
             } else {
                 $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).".item_id = '".encode(AS_DB, $item_id)."'";
                 $result = $this->_db_connector->performQuery($query);
                 if (!isset($result)) {
                     trigger_error('Problems selecting one portfolio item.', E_USER_WARNING);
                 } elseif (!empty($result[0])) {
                     if ($this->_cache_on) {
                         $this->_cached_items[$result[0]['item_id']] = $result[0];
                     }
                     $portfolio_item = $this->_buildItem($result[0]);
                     unset($result);
                 } else {
                     trigger_error('Problems selecting announcement item ['.$item_id.'].', E_USER_WARNING);
                 }
             }
         }

         return $portfolio_item;
     }

     public function getItemList(array $id_array)
     {
         return $this->_getItemList(CS_PORTFOLIO_TYPE, $id_array);
     }

      public function getNewItem()
      {
          return new cs_portfolio_item($this->_environment);
      }

    public function _update($portfolio_item)
    {
        parent::_update($portfolio_item);
        $modification_date = getCurrentDateTimeInMySQL();
        $portfolio_template = $portfolio_item->getTemplate();
        if (empty($portfolio_template)) {
            $template = -1;
        } else {
            $template = $portfolio_item->getTemplate();
        }
        $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'modification_date="'.$modification_date.'",'.
                 'modifier_id="'.encode(AS_DB, $portfolio_item->getModificatorID()).'",'.
                 'title="'.encode(AS_DB, $portfolio_item->getTitle()).'",'.
                 'description="'.encode(AS_DB, $portfolio_item->getDescription()).'",'.
                 'template="'.encode(AS_DB, $template).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $portfolio_item->getItemID()).'"';

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating portfolio.', E_USER_WARNING);
        } else {
            unset($result);
        }

        $this->_updateExternalViewer($portfolio_item);
        $this->_updateExternalTemplate($portfolio_item);

        unset($portfolio_item);
    }

    public function _updateExternalTemplate($portfolio_item)
    {
        $query = '
  		DELETE FROM
  			'.$this->addDatabasePrefix('template_portfolio')."
  		WHERE
  			p_id = '".encode(AS_DB, $portfolio_item->getItemID())."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems updating portfolio.', E_USER_WARNING);
        }

        $externalTemplateArray = $portfolio_item->getExternalTemplate();
        if ($externalTemplateArray and is_array($externalTemplateArray)) {
            foreach ($portfolio_item->getExternalTemplate() as $viewer) {
                if (!empty($viewer)) {
                    $query = '
		  		INSERT INTO
		  			'.$this->addDatabasePrefix('template_portfolio')."
		  		(
		  			p_id,
		  			u_id
		  		) VALUES (
		  			'".encode(AS_DB, $portfolio_item->getItemID())."',
		  			'".encode(AS_DB, $viewer)."'
		  		);
	  		";
                    $result = $this->_db_connector->performQuery($query);
                    if (!isset($result)) {
                        trigger_error('Problems updating portfolio.', E_USER_WARNING);
                    }
                }
            }
        }
    }

    public function _updateExternalViewer($portfolio_item)
    {
        $query = '
  		DELETE FROM
  			'.$this->addDatabasePrefix('user_portfolio')."
  		WHERE
  			p_id = '".encode(AS_DB, $portfolio_item->getItemID())."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems updating portfolio.', E_USER_WARNING);
        }

        $externalViewerArray = $portfolio_item->getExternalViewer();
        if ($externalViewerArray and is_array($externalViewerArray)) {
            foreach ($portfolio_item->getExternalViewer() as $viewer) {
                if (!empty($viewer)) {
                    $query = '
		  		INSERT INTO
		  			'.$this->addDatabasePrefix('user_portfolio')."
		  		(
		  			p_id,
		  			u_id
		  		) VALUES (
		  			'".encode(AS_DB, $portfolio_item->getItemID())."',
		  			'".encode(AS_DB, $viewer)."'
		  		);
	  		";
                    $result = $this->_db_connector->performQuery($query);
                    if (!isset($result)) {
                        trigger_error('Problems updating portfolio.', E_USER_WARNING);
                    }
                }
            }
        }
    }

    public function _create($portfolio_item)
    {
        // TODO: shouldn't the context id be the private room id???
        $modification_date = getCurrentDateTimeInMySQL();
        $portal_id = $this->_environment->getCurrentPortalID();
        $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
                 'context_id="'.encode(AS_DB, $portal_id).'",'.
                 'modification_date="'.$modification_date.'",'.
                 'type="portfolio",'.
                 'draft="'.encode(AS_DB, 1).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating portfolio.', E_USER_WARNING);
            $this->_create_id = null;
        } else {
            $this->_create_id = $result;
            $portfolio_item->setItemID($this->getCreateID());
            $portfolio_item->setDraftStatus(1);
            $this->_newPortfolio($portfolio_item);
            unset($result);
        }
        unset($portfolio_item);
    }

    public function _newPortfolio($portfolio_item)
    {
        $user = $portfolio_item->getCreatorItem();
        $modification_date = getCurrentDateTimeInMySQL();

        $portfolio_template = $portfolio_item->getTemplate();
        if (empty($portfolio_template)) {
            $template = -1;
        } else {
            $template = $portfolio_item->getTemplate();
        }

        $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'item_id="'.encode(AS_DB, $portfolio_item->getItemID()).'",'.
                 'creator_id="'.encode(AS_DB, $portfolio_item->getCreatorID()).'",'.
                 'modifier_id="'.encode(AS_DB, $portfolio_item->getModificatorID()).'",'.
                 'modification_date="'.$modification_date.'",'.
                 'creation_date="'.$modification_date.'",'.
                 'title="'.encode(AS_DB, $portfolio_item->getTitle()).'",'.
                 'template="'.encode(AS_DB, $template).'",'.
                 'description="'.encode(AS_DB, $portfolio_item->getDescription()).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating portfolio.', E_USER_WARNING);
        } else {
            $this->_updateExternalViewer($portfolio_item);
            $this->_updateExternalTemplate($portfolio_item);
            unset($result);
        }
        unset($portfolio_item);
    }

    public function delete(int $itemId): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'deletion_date="'.$current_datetime.'"'.
                 ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting portfolio.', E_USER_WARNING);
        } else {
            parent::delete($itemId);

            $this->deletePortfolioTags($item_id);
            $this->deletePortfolioAnnotations($item_id);
            $this->deletePortfolioUsers($item_id);
            $this->deletePortfolioTemplateUsers($item_id);
        }
    }

/***********NEW FUNCTIONS****************/
    public function getActivatedIDArray($userId)
    {
        $query = '
  		SELECT
  			user_portfolio.p_id
  		FROM
  			'.$this->addDatabasePrefix('user_portfolio').' AS user_portfolio
  		LEFT JOIN
  			'.$this->addDatabasePrefix($this->_db_table)." AS portfolio
  		ON
  			user_portfolio.p_id = portfolio.item_id
  		WHERE
  			user_portfolio.u_id = '".encode(AS_DB, $userId)."' AND
  			portfolio.deletion_date IS NULL;
  	";
        $result = $this->_db_connector->performQuery($query);

        if (!isset($result)) {
            trigger_error('Problems getting portfolio ids.', E_USER_WARNING);
        }

        $return = [];
        foreach ($result as $row) {
            $return[] = $row['p_id'];
        }

        return $return;
    }

public function getPortfolioTags($portfolioId)
{
    $query = '
  		SELECT
  			tag_portfolio.t_id,
  			tag_portfolio.row,
  			tag_portfolio.column,
  			tag_portfolio.description,
  			tag.title
  		FROM
  			'.$this->addDatabasePrefix('tag_portfolio').' AS tag_portfolio
  		LEFT JOIN
  			'.$this->addDatabasePrefix('tag')." AS tag
  		ON
  			tag_portfolio.t_id = tag.item_id
  		WHERE
  			tag_portfolio.p_id = '".encode(AS_DB, $portfolioId)."'
  		ORDER BY
  			tag_portfolio.row, tag_portfolio.column
  	";
    $result = $this->_db_connector->performQuery($query);

    if (!isset($result)) {
        trigger_error('Problems getting portfolio tags.', E_USER_WARNING);
    }

    return $result;
}

    public function getPortfolioData($portfolioIdArray, $tagIdArray)
    {
        $query = '
  		SELECT
  			tag_portfolio.p_id,
  			tag_portfolio.t_id,
  			tag_portfolio.row,
  			tag_portfolio.column
  		FROM
  			'.$this->addDatabasePrefix('tag_portfolio').' AS tag_portfolio
  		WHERE
  			tag_portfolio.p_id IN ('.encode(AS_DB, implode(', ', $portfolioIdArray)).') AND
  			tag_portfolio.t_id IN ('.encode(AS_DB, implode(', ', $tagIdArray)).')
  	';
        $result = $this->_db_connector->performQuery($query);

        if (!isset($result)) {
            trigger_error('Problems getting portfolio tags.', E_USER_WARNING);
        }

        $return = [];
        foreach ($result as $row) {
            $return[$row['p_id']][] = ['t_id' => $row['t_id'], 'row' => $row['row'], 'column' => $row['column']];
        }

        return $return;
    }

    public function addTagToPortfolio($portfolioId, $tagId, $position, $index, $description)
    {
        if ('row' === $position) {
            $row = $index;
            $column = 0;
        } else {
            $row = 0;
            $column = $index;
        }

        $query = '
  		INSERT INTO
  			'.$this->addDatabasePrefix('tag_portfolio')."
  		(
  			p_id,
  			t_id,
  			`row`,
  			`column`,
  			`description`
  		) VALUES (
  			'".encode(AS_DB, $portfolioId)."',
  			'".encode(AS_DB, $tagId)."',
  			'".encode(AS_DB, $row)."',
  			'".encode(AS_DB, $column)."',
  			'".encode(AS_DB, $description)."'
  		);
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems storing tag for portfolio.', E_USER_WARNING);
        }
    }

    public function replaceTagForPortfolio($portfolioId, $tagId, $oldTagId, $description)
    {
        $query = '
  		UPDATE
  			'.$this->addDatabasePrefix('tag_portfolio')."
  		SET
  			t_id = '".encode(AS_DB, $tagId)."',
  			description = '".encode(AS_DB, $description)."'
  		WHERE
  			t_id = '".encode(AS_DB, $oldTagId)."' AND
  			p_id = '".encode(AS_DB, $portfolioId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems replacing tag for portfolio.', E_USER_WARNING);
        }
    }

    public function deletePortfolioTag($portfolioId, $tagId)
    {
        $query = '
  		DELETE FROM
  			'.$this->addDatabasePrefix('tag_portfolio')."
  		WHERE
  			p_id = '".encode(AS_DB, $portfolioId)."' AND
  			t_id = '".encode(AS_DB, $tagId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems deleting tag for portfolio.', E_USER_WARNING);
        }
    }

public function deletePortfolioTags($portfolioId)
{
    $query = '
  		DELETE FROM
  			'.$this->addDatabasePrefix('tag_portfolio')."
  		WHERE
  		p_id = '".encode(AS_DB, $portfolioId)."';
  	";
    $result = $this->_db_connector->performQuery($query);
    if (!isset($result)) {
        trigger_error('Problems deleting tags for portfolio.', E_USER_WARNING);
    }
}

    public function deletePortfolioAnnotations($portfolioId)
    {
        $query = '
	  	DELETE FROM
	  		'.$this->addDatabasePrefix('annotation_portfolio')."
	  	WHERE
	  		p_id = '".encode(AS_DB, $portfolioId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems deleting annotations for portfolio.', E_USER_WARNING);
        }
    }

    public function deletePortfolioUsers($portfolioId)
    {
        $query = '
	  	DELETE FROM
	  		'.$this->addDatabasePrefix('user_portfolio')."
	  	WHERE
	  		p_id = '".encode(AS_DB, $portfolioId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems deleting users for portfolio.', E_USER_WARNING);
        }
    }

    public function deletePortfolioTemplateUsers($portfolioId)
    {
        $query = '
	  	DELETE FROM
	  		'.$this->addDatabasePrefix('template_portfolio')."
	  	WHERE
	  		p_id = '".encode(AS_DB, $portfolioId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems deleting users for portfolio.', E_USER_WARNING);
        }
    }

    public function updatePortfolioTagPosition($portfolioId, $tagId, $row, $column)
    {
        $query = '
  		UPDATE
  			'.$this->addDatabasePrefix('tag_portfolio')."
  		SET
  			`row` = '".encode(AS_DB, $row)."',
  			`column` = '".encode(AS_DB, $column)."'
  		WHERE
  			p_id = '".encode(AS_DB, $portfolioId)."' AND
  			t_id = '".encode(AS_DB, $tagId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems updating tag position for portfolio.', E_USER_WARNING);
        }
    }

    public function getExternalViewer($portfolioId)
    {
        $query = '
	  	SELECT
	  		u_id
	  	FROM
	  		'.$this->addDatabasePrefix('user_portfolio')."
	  	WHERE
	  		p_id= '".encode(AS_DB, $portfolioId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems getting user ids.', E_USER_WARNING);
        }

        $userArray = [];
        foreach ($result as $row) {
            $userArray[] = $row['u_id'];
        }

        return $userArray;
    }

    public function getPortfolioForExternalViewer($userId)
    {
        $query = '
	  	SELECT
	  		p_id
	  	FROM
	  		'.$this->addDatabasePrefix('user_portfolio')."
	  	WHERE
	  		u_id = '".encode(AS_DB, $userId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems getting user ids.', E_USER_WARNING);
        }

        $portfolioArray = [];
        foreach ($result as $row) {
            $portfolioArray[] = $row['p_id'];
        }

        return $portfolioArray;
    }

      public function removeExternalViewer($portfolioId, $userId)
      {
          $query = '
            DELETE FROM
                '.$this->addDatabasePrefix('user_portfolio')."
            WHERE
                u_id = '".encode(AS_DB, $userId)."' AND
                p_id = '".encode(AS_DB, $portfolioId)."';
        ";

          $result = $this->_db_connector->performQuery($query);
          if (!isset($result)) {
              trigger_error('Problems getting user ids.', E_USER_WARNING);
          }
      }

    public function getPortfolioUserForExternalViewer($creatorId)
    {
        $query = '
  		SELECT
  			user_portfolio.u_id
  		FROM
  			'.$this->addDatabasePrefix('user_portfolio').'
  		LEFT JOIN
  			'.$this->addDatabasePrefix('portfolio')."
  		ON
  			user_portfolio.p_id = portfolio.item_id
  		WHERE
  			portfolio.creator_id = '".encode(AS_DB, $creatorId)."';
  	";

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems getting user ids.', E_USER_WARNING);
        }

        $userArray = [];
        foreach ($result as $row) {
            $userArray[] = $row['u_id'];
        }

        return $userArray;
    }

    public function getAnnotationCountForPortfolio($portfolioId)
    {
        $query = '
  		SELECT
  			COUNT(a_id) AS count,
  			`row`,
  			`column`
  		FROM
  			'.$this->addDatabasePrefix('annotation_portfolio')."
  		WHERE
  			p_id = '".encode(AS_DB, $portfolioId)."'
  		GROUP BY
  			`row`, `column`;
  	";
        $result = $this->_db_connector->performQuery($query);

        if (!isset($result)) {
            trigger_error('Problems getting portfolio annotation count.', E_USER_WARNING);
        }

        $return = [];
        foreach ($result as $row) {
            $return[$row['row']][$row['column']] = $row['count'];
        }

        return $return;
    }

    public function getPortfolioId($annotationId)
    {
        $query = '
	  	SELECT
	  		p_id
		FROM
		  	'.$this->addDatabasePrefix('annotation_portfolio')."
	  	WHERE
	  		a_id = '".encode(AS_DB, $annotationId)."'
  	";
        $result = $this->_db_connector->performQuery($query);

        if (!isset($result)) {
            trigger_error('Problems getting portfolio annotation ids.', E_USER_WARNING);
        }

        return $result[0]['p_id'];
    }

    public function getAnnotationIdsForPortfolioCell($portfolioId, $row, $column)
    {
        $query = '
	  	SELECT
	  		a_id
		FROM
		  	'.$this->addDatabasePrefix('annotation_portfolio')."
	  	WHERE
	  		p_id = '".encode(AS_DB, $portfolioId)."' AND
	  		`row` = '".encode(AS_DB, $row)."' AND
	  		`column` = '".encode(AS_DB, $column)."'
  	";
        $result = $this->_db_connector->performQuery($query);

        if (!isset($result)) {
            trigger_error('Problems getting portfolio annotation ids.', E_USER_WARNING);
        }

        $return = [];
        foreach ($result as $row) {
            $return[] = $row['a_id'];
        }

        return $return;
    }

    public function setPortfolioAnnotation($portfolioId, $annotationId, $portfolioRow, $portfolioColumn)
    {
        $query = '
  		INSERT INTO
  			'.$this->addDatabasePrefix('annotation_portfolio')."
  		(
  			p_id,
  			a_id,
  			`row`,
  			`column`
  		) VALUES (
  			'".encode(AS_DB, $portfolioId)."',
  			'".encode(AS_DB, $annotationId)."',
  			'".encode(AS_DB, $portfolioRow)."',
  			'".encode(AS_DB, $portfolioColumn)."'
  		)
  	";

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems storing annotation - portfolio reference.', E_USER_WARNING);
        }
    }

    public function deletePortfolioAnnotation($portfolioId, $annotationId)
    {
        $query = '
  		DELETE FROM
  			'.$this->addDatabasePrefix('annotation_portfolio')."
  		WHERE
  			p_id = '".encode(AS_DB, $portfolioId)."' AND
  			a_id = '".encode(AS_DB, $annotationId)."'
  	";

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems deleting annotation for portfolio reference.', E_USER_WARNING);
        }
    }

    // portfolio template
    public function getPortfolioForExternalTemplate($userId)
    {
        $query = '
	  	SELECT
	  		p_id
	  	FROM
	  		'.$this->addDatabasePrefix('template_portfolio')."
	  	WHERE
	  		u_id = '".encode(AS_DB, $userId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems getting user ids.', E_USER_WARNING);
        }

        $portfolioArray = [];
        foreach ($result as $row) {
            $portfolioArray[] = $row['p_id'];
        }

        return $portfolioArray;
    }

    public function getExternalTemplate($portfolioId)
    {
        $query = '
	  	SELECT
	  		u_id
	  	FROM
	  		'.$this->addDatabasePrefix('template_portfolio')."
	  	WHERE
	  		p_id= '".encode(AS_DB, $portfolioId)."';
  	";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems getting user ids.', E_USER_WARNING);
        }

        $userArray = [];
        foreach ($result as $row) {
            $userArray[] = $row['u_id'];
        }

        return $userArray;
    }

    public function getTemplatePortfoliosByCreatorID($creatorID)
    {
        $query = '
	  	SELECT
	  		item_id, title
	  	FROM
	  		'.$this->addDatabasePrefix('portfolio')."
	  	WHERE
	  		creator_id = '".encode(AS_DB, $creatorID)."' AND
	  		template = '1' AND
	  		".$this->addDatabasePrefix('portfolio').'.deletion_date IS NULL;
  	';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems getting user ids.', E_USER_WARNING);
        }

        $userArray = [];
        foreach ($result as $row) {
            $userArray[$row['item_id']] = $row['title'];
        }

        return $userArray;
    }
}
