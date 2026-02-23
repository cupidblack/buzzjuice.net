import { useStore } from '@p2p/js/frontend/App/store';
import { ArrowIcon, HeartIcon } from '@p2p/Components/Icons';
import { Button } from '@p2p/Components/Button';

import styles from './styles.module.scss';

const { __ } = wp.i18n;

const SponsorGrid = () => {

	const [ { campaign, sponsors } ] = useStore();

	return (
		<div className={ styles.container }>
			<h2 className={ styles.header }>
				{ campaign.sponsor_section_heading || __( 'Our Wonderful Sponsors', 'give-peer-to-peer' ) }
			</h2>
			<div className={ styles.gridcontainer }>
				<div className={ styles.grid }>
					{ ( sponsors && sponsors.length > 0 ) ? (
						<>
							{ sponsors.map( ( sponsor ) => {
								const image = <img key={ sponsor.id } src={ sponsor.sponsor_image } alt={ sponsor.sponsor_name }/>;
								if ( 'disabled' === campaign.sponsor_linking ) {
									return image;
								} else {
									const rel = ( 'nofollow' == campaign.sponsor_linking ) ? 'nofollow sponsored' : 'external';
									return <a key={ sponsor.sponsor_url } target="_blank" rel={ rel } href={ sponsor.sponsor_url }>{ image }</a>;
								}
							} ) }
						</>
					) : (
						<div className={ styles.sponsorText }>
							<HeartIcon color={ campaign.primary_color }/>
							<h3>{ __( 'Sponsor this campaign!', 'give-peer-to-peer' ) }</h3>
							<div>{ __( 'Promote your brand alongside this campaign and help the cause.', 'give-peer-to-peer' ) }</div>
							{ campaign.sponsor_application_page && (
								<Button color={Button.colors.secondary} as="a" href= {campaign.sponsor_application_page }>
									{ __( 'Sponsor Campaign', 'give-peer-to-peer' ) }
								</Button>
							) }
						</div>
					) }
				</div>
			</div>
			{ ( sponsors && sponsors.length > 0 && campaign.sponsor_application_page ) && (
				<div className={ styles.becomeSponsor }>
					<a href={ campaign.sponsor_application_page } style={ { color: campaign.primary_color } }>
						{ __( 'Become a Sponsor', 'give-peer-to-peer' ) }
						<ArrowIcon height={ 12 } width={ 22 } color={ campaign.primary_color } />
					</a>
				</div>
			) }
		</div>
	);
};

export {
	SponsorGrid,
};
