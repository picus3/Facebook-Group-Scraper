CREATE TABLE IF NOT EXISTS `comments` (
  `id` varchar(50) NOT NULL,
  `message` varchar(15000) NOT NULL,
  `created_time` int(11) NOT NULL,
  `poster` varchar(40) NOT NULL,
  `like_count` int(11) NOT NULL,
  `parent_post_id` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `posts` (
  `id` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `created_time` int(10) NOT NULL,
  `updated_time` int(10) NOT NULL,
  `poster` varchar(40) NOT NULL,
  `message` varchar(15000) NOT NULL,
  `description` varchar(2000) NOT NULL,
  `link` varchar(2000) NOT NULL,
  `picture` varchar(2000) NOT NULL,
  `source` varchar(2000) NOT NULL,
  `total_likes` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
