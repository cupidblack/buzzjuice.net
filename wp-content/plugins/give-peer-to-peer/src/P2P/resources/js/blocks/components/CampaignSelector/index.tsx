import {__} from '@wordpress/i18n';
import {useState} from '@wordpress/element';
import ReactSelect from 'react-select';

import {ArrowIcon} from '@p2p/Components/Icons';
import {Button} from '@p2p/Components';

import styles from './style.module.scss';
import GiveLogo from '@p2p/Components/SVGImages/GiveLogo';

const CampaignSelector = ({campaigns, setAttributes}) => {
    const {campaignOptions, isLoading} = campaigns;

    const [campaignId, setCampaignId] = useState();

    const handleSelectChange = (option) => setCampaignId(option?.value);

    const confirmSelection = () => {
        setAttributes({
            ['id']: campaignId,
            ['align']: 'wide',
        });
    };

    const styleConfig = {
        input: (baseStyles, state) => ({
            ...baseStyles,
            height: '3rem',
        }),
        option: (baseStyles, state) => ({
            ...baseStyles,
            paddingTop: '0.8rem',
            paddingBottom: '0.8rem',
            fontSize: '1rem',
        }),
        control: (baseStyles, state) => ({
            ...baseStyles,
            fontSize: '1rem',
            width: 500,
            border: state.isFocused ? '1px solid #28c77b' : 0,
        }),
    };

    // @ts-ignore
    return (
        <form>
            <label className={styles.campaignSelectorLabel} id={'campaign_selector'} htmlFor={'campaign_selector'}>
                <GiveLogo />
                <span> {__('Choose a campaign', 'give-peer-to-peer')}</span>
                <ReactSelect
                    className={styles.campaignSelector}
                    name={'campaign_selector'}
                    inputId={'campaign_selector'}
                    aria-labelledby={'campaign_selector'}
                    placeholder={
                        isLoading
                            ? __('Loading Campaigns...', 'give-peer-to-peer')
                            : __('Select...', 'give-peer-to-peer')
                    }
                    defaultValue={campaignId}
                    onChange={handleSelectChange}
                    options={campaignOptions}
                    isLoading={isLoading}
                    loadingMessage={() => <>{__('Loading Campaigns...', 'give-peer-to-peer')}</>}
                    isClearable
                    isSearchable
                    theme={(theme) => ({
                        ...theme,
                        colors: {
                            ...theme.colors,
                            primary: '#27ae60',
                        },
                    })}
                    styles={styleConfig}
                />
                <div className={styles.btnContainer}>
                    <Button onClick={confirmSelection} as={'button'} iconAfter={ArrowIcon} isDisabled={!campaignId}>
                        {__('Continue', 'give-peer-to-peer')}
                    </Button>
                </div>
            </label>
        </form>
    );
};

export default CampaignSelector;
