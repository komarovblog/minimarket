<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/** Trusted validator for both public and operator-created review content. */
final class ReviewData
{
    public static function normalize(array $input,bool $media=false):array
    {
        foreach(['author_name','title','body','photo_path','video_url'] as $key) {
            if(!is_scalar($input[$key]??''))throw new \DomainException('COM_MINIMARKET_REVIEW_INVALID');
        }
        $name=trim(preg_replace('/\s+/u',' ',strip_tags((string)($input['author_name']??'')))??'');
        $title=trim(strip_tags((string)($input['title']??'')));
        $body=trim(strip_tags((string)($input['body']??'')));
        $rating=(int)($input['rating']??0);
        if(mb_strlen($name)<2||mb_strlen($name)>120 || $rating<1||$rating>5
            ||mb_strlen($title)>140||mb_strlen($body)<10||mb_strlen($body)>5000
            ||preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/u',$body)) {
            throw new \DomainException('COM_MINIMARKET_REVIEW_INVALID');
        }
        $photo='';
        $video='';
        if($media) {
            $rawPhoto=explode('#',trim((string)($input['photo_path']??'')),2)[0];
            if($rawPhoto!=='') {
                if(strlen($rawPhoto)>1024||!preg_match('~^images/[a-zA-Z0-9_./ -]+\.(?:jpg|jpeg|png|webp|avif)$~iD',$rawPhoto)
                   ||str_contains($rawPhoto,'..')||str_contains($rawPhoto,'//')) {
                    throw new \DomainException('COM_MINIMARKET_REVIEW_INVALID');
                }
                $photo=$rawPhoto;
            }
            $rawVideo=trim((string)($input['video_url']??''));
            if($rawVideo!=='') {
                if(strlen($rawVideo)>1024 || !preg_match('~^(?:images|media)/[a-zA-Z0-9_./ -]+\.(?:mp4|webm)$~iD',$rawVideo)
                   && !preg_match('~^https://(?:www\.)?(?:youtube\.com/watch\?v=[a-zA-Z0-9_-]{11}|youtu\.be/[a-zA-Z0-9_-]{11}|rutube\.ru/video/[a-zA-Z0-9]{12,50}/?)$~D',$rawVideo)) {
                    throw new \DomainException('COM_MINIMARKET_REVIEW_INVALID');
                }
                if(str_contains($rawVideo,'..')) throw new \DomainException('COM_MINIMARKET_REVIEW_INVALID');
                $video=$rawVideo;
            }
        }
        return ['author_name'=>$name,'rating'=>$rating,'title'=>$title,'body'=>$body,
            'photo_path'=>$photo,'video_url'=>$video];
    }
}
