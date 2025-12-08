import {useSelector} from "../../js/frontend/App/store";

import styles from './style.module.scss';

const getCampaignTitleString = ( title ) => {
    if ( Array.isArray( title ) ) {
        return title[ title.length - 1 ];
    }
    return title;
}

const CampaignLogo = () => {

    const {campaignLogo, campaignTitle} = useSelector((state) => ({
        campaignLogo: state.campaign.campaign_logo,
        campaignTitle: getCampaignTitleString(state.campaign.campaign_title),
    }));

    if( !campaignLogo || typeof campaignLogo != 'string' ) {
        return null;
    }

    return (
        <img className={ styles.logo } src={ campaignLogo } alt={ campaignTitle }/>
    );
};

export default CampaignLogo;
